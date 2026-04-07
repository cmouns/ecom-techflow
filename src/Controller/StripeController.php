<?php

namespace App\Controller;

use App\Entity\Order;
use App\Entity\OrderItem;
use App\Entity\User;
use App\Repository\DeliveryInfoRepository;
use App\Repository\OrderItemRepository;
use App\Repository\OrderRepository;
use App\Service\CartService;
use Doctrine\ORM\EntityManagerInterface;
use Stripe\Checkout\Session;
use Stripe\Stripe;
use Stripe\Webhook;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class StripeController extends AbstractController
{
    /**
     * Prépare la commande en base de données et redirige vers la page de paiement Stripe.
     */
    #[Route('/stripe/checkout', name: 'app_stripe_checkout')]
    public function index(
        CartService $cartService,
        EntityManagerInterface $em,
        RequestStack $requestStack,
        DeliveryInfoRepository $deliveryInfoRepository,
        // Utilise l'attribut Autowire pour récupérer ma clé secrète directement depuis le fichier .env
        #[Autowire('%env(STRIPE_SECRET_KEY)%')] string $stripeSecretKey,
    ): Response {
        /** @var User|null $user */
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        // Initialisation de Stripe avec ma clé secrète
        Stripe::setApiKey($stripeSecretKey);
        $cart = $cartService->getFullCart();
        $session = $requestStack->getSession();

        // Récupère l'adresse de livraison choisie à l'étape précédente
        $deliveryId = $session->get('address_id');
        $deliveryInfo = $deliveryId ? $deliveryInfoRepository->find($deliveryId) : null;

        if (!$deliveryInfo) {
            return $this->redirectToRoute('app_checkout_index');
        }

        // Formate l'adresse en un seul bloc de texte pour la garder figer dans la commande
        $formattedAddress = $deliveryInfo->getFirstName().' '.$deliveryInfo->getLastName()."\n".
                            $deliveryInfo->getPhone()."\n".
                            $deliveryInfo->getAddress()."\n".
                            $deliveryInfo->getPostalCode().' '.$deliveryInfo->getCity()."\n".
                            $deliveryInfo->getCountry();

        // Création de la commande avec le statut "PENDING"
        $order = new Order();
        $order->setUser($user);
        $order->setCreatedAt(new \DateTimeImmutable());

        // Génération d'une référence unique
        $datePrefix = (new \DateTime())->format('Ym');
        $randomPart = strtoupper(substr(bin2hex(random_bytes(2)), 0, 4));
        $order->setReference($datePrefix.'-'.$randomPart);

        $order->setStatus(Order::STATUS_PENDING); // Pas encore payé
        $order->setDeliveryAddress($formattedAddress);

        $em->persist($order);

        // Préparation du tableau des articles pour Stripe
        $lineItems = [];
        $totalPrice = 0;

        foreach ($cart as $item) {
            $product = $item['product'];
            $quantity = $item['quantity'];
            $price = $product->getPrice();

            // Sauvegarde chaque ligne de la commande en base
            $orderItem = new OrderItem();
            $orderItem->setProduct($product);
            $orderItem->setPurchase($order);
            $orderItem->setProductName($product->getName());
            $orderItem->setProductPrice($price);
            $orderItem->setQuantity($quantity);

            $em->persist($orderItem);

            $totalPrice += ($price * $quantity);

            // Ajoute l'article au format attendu par l'API Stripe
            $lineItems[] = [
                'price_data' => [
                    'currency' => 'eur',
                    'unit_amount' => $price, // Stripe gère les prix en centimes
                    'product_data' => [
                        'name' => $product->getName(),
                    ],
                ],
                'quantity' => $quantity,
            ];
        }

        // Ajout des frais de port si le total est inférieur à 150€ (15000 centimes)
        if ($totalPrice <= 15000) {
            $shippingCost = 990;
            $totalPrice += $shippingCost;

            $lineItems[] = [
                'price_data' => [
                    'currency' => 'eur',
                    'unit_amount' => $shippingCost,
                    'product_data' => [
                        'name' => 'Frais de livraison',
                    ],
                ],
                'quantity' => 1,
            ];
        }

        $order->setTotalPrice($totalPrice);

        // Création de la session de paiement Stripe
        $checkoutSession = Session::create([
            'payment_method_types' => ['card'],
            'line_items' => $lineItems,
            'mode' => 'payment',
            'success_url' => $this->generateUrl('app_stripe_success', [], UrlGeneratorInterface::ABSOLUTE_URL),
            'cancel_url' => $this->generateUrl('app_stripe_cancel', [], UrlGeneratorInterface::ABSOLUTE_URL),
        ]);

        // Enregistre l'id de la session Stripe dans ma commande pour faire le lien plus tard
        $order->setStripeSessionId($checkoutSession->id);
        $em->flush();

        // Redirige l'utilisateur vers la page sécurisée de Stripe
        return $this->redirect($checkoutSession->url, 303);
    }

    /**
     * Page affichée si le paiement est un succès.
     */
    #[Route('/stripe/success', name: 'app_stripe_success')]
    public function success(CartService $cartService): Response
    {
        // Le client a payé, on peut vider son panier
        $cartService->empty();

        return $this->render('stripe/success.html.twig');
    }

    /**
     * Page affichée si le client annule le paiement.
     */
    #[Route('/stripe/cancel', name: 'app_stripe_cancel')]
    public function cancel(): Response
    {
        return $this->render('stripe/cancel.html.twig');
    }

    /**
     * Webhook appelé par Stripe pour confirmer le paiement de façon sécurisée.
     */
    #[Route('/stripe/webhook', name: 'app_stripe_webhook', methods: ['POST'])]
    public function webhook(
        EntityManagerInterface $em,
        OrderRepository $orderRepository,
        OrderItemRepository $orderItemRepository,
        MailerInterface $mailer,
        #[Autowire('%env(STRIPE_WEBHOOK_SECRET)%')] string $webhookSecret,
        Request $request,
    ): Response {
        $payload = $request->getContent();
        $sigHeader = $request->headers->get('Stripe-Signature');

        try {
            // Vérifie que la requête vient bien de Stripe grâce à la signature secrète
            $event = Webhook::constructEvent($payload, $sigHeader, $webhookSecret);
        } catch (\Exception $e) {
            return new Response('Signature invalide', 400);
        }

        // Si l'événement est bien une validation de paiement
        if ('checkout.session.completed' === $event->type) {
            $session = $event->data->object;
            $sessionId = $session->id;

            // Retrouve la commande en attente grâce à l'ID de session enregistré
            $order = $orderRepository->findOneBy(['stripeSessionId' => $sessionId]);

            if ($order) {
                // Passe la commande en statut PAID
                $order->setStatus(Order::STATUS_PAID);

                // Met à jour les stocks des produits
                $orderItems = $orderItemRepository->findBy(['purchase' => $order]);
                foreach ($orderItems as $detail) {
                    $product = $detail->getProduct();
                    $quantityBought = $detail->getQuantity();

                    $newStock = $product->getStock() - $quantityBought;
                    // Évite d'avoir un stock négatif
                    if ($newStock < 0) {
                        $newStock = 0;
                    }
                    $product->setStock($newStock);
                }

                $em->flush();

                // Envoie l'email de confirmation au client
                $email = (new Email())
                    ->from('contact@tech-flow.com')
                    ->to($order->getUser()->getEmail())
                    ->subject('Confirmation de votre commande n°'.$order->getReference())
                    ->html('
                        <h1>Merci pour votre achat !</h1>
                        <p>Votre commande a bien été validée et est en cours de préparation.</p>
                        <p>À bientôt sur TechFlow !</p>
                    ');

                $mailer->send($email);
            }
        }

        // Répond un code 200 à Stripe pour lui dire qu'on a bien reçu l'information
        return new Response('Success', 200);
    }
}