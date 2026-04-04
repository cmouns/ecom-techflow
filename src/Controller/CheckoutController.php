<?php

namespace App\Controller;

use App\Entity\DeliveryInfo;
use App\Entity\User;
use App\Form\DeliveryInfoType;
use App\Service\CartService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/checkout', name: 'app_checkout_')]
#[IsGranted('ROLE_USER')] // Il faut obligatoirement un compte pour commander
class CheckoutController extends AbstractController
{
    /**
     * Choix ou création d'une adresse de livraison.
     */
    #[Route('/', name: 'index')]
    public function index(CartService $cartService, Request $request, EntityManagerInterface $em): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $cart = $cartService->getFullCart();

        // Évite que l'utilisateur accède au checkout si son panier est vide
        if (empty($cart)) {
            $this->addFlash('warning', 'Votre panier est vide.');

            return $this->redirectToRoute('app_product_index');
        }

        // Prépare un formulaire pour ajouter une nouvelle adresse si besoin
        $deliveryInfo = new DeliveryInfo();
        $form = $this->createForm(DeliveryInfoType::class, $deliveryInfo);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Lie la nouvelle adresse à l'utilisateur actuel
            $deliveryInfo->setUser($user);
            $em->persist($deliveryInfo);
            $em->flush();

            $this->addFlash('success', 'Adresse de livraison ajoutée !');

            return $this->redirectToRoute('app_checkout_index');
        }

        return $this->render('checkout/index.html.twig', [
            'items' => $cart,
            'total' => $cartService->getTotal(),
            'userAddresses' => $user->getDeliveryInfos(), // Affiche ses adresses déjà enregistrées
            'addressForm' => $form->createView(),
        ]);
    }

    /**
     * Récapitulatif de la commande et calcul des frais de port.
     */
    #[Route('/confirm/{id}', name: 'confirm')]
    public function confirm(DeliveryInfo $deliveryInfo, CartService $cartService, Request $request, EntityManagerInterface $em): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        // Vérifie que l'adresse choisie appartient bien à l'utilisateur connecté
        if ($deliveryInfo->getUser() !== $user) {
            throw $this->createAccessDeniedException();
        }

        $cart = $cartService->getFullCart();

        // Une sécurité de plus au cas où le panier se viderait entre temps
        if (empty($cart)) {
            return $this->redirectToRoute('app_product_index');
        }

        // Possibilité d'ajouter une adresse même à cette étape
        $newDeliveryInfo = new DeliveryInfo();
        $form = $this->createForm(DeliveryInfoType::class, $newDeliveryInfo);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $newDeliveryInfo->setUser($user);
            $em->persist($newDeliveryInfo);
            $em->flush();

            return $this->redirectToRoute('app_checkout_confirm', ['id' => $deliveryInfo->getId()]);
        }

        // Stocke l'id de l'adresse en session pour la retrouver lors de la création finale de la commande
        $request->getSession()->set('address_id', $deliveryInfo->getId());

        $subtotal = $cartService->getTotal();

        // Frais de port gratuits au-delà de 150€ (15000 centimes) sinon 9.90€
        $shippingCost = ($subtotal > 15000) ? 0 : 990;
        $total = $subtotal + $shippingCost;

        return $this->render('checkout/confirm.html.twig', [
            'deliveryInfo' => $deliveryInfo,
            'items' => $cart,
            'subtotal' => $subtotal,
            'shippingCost' => $shippingCost,
            'total' => $total,
            'userAddresses' => $user->getDeliveryInfos(),
            'addressForm' => $form->createView(),
        ]);
    }
}
