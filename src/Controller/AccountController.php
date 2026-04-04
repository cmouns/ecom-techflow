<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\ChangePasswordType;
use App\Form\ProfileType;
use App\Repository\OrderItemRepository;
use App\Repository\OrderRepository;
use Doctrine\ORM\EntityManagerInterface;
use Dompdf\Dompdf;
use Dompdf\Options;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/compte', name: 'app_account_')]
#[IsGranted('IS_AUTHENTICATED_FULLY')] // Il faut être connecté pour arriver ici
class AccountController extends AbstractController
{
    /**
     * Affiche la page d'accueil du compte utilisateur.
     */
    #[Route('/', name: 'index', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('account/index.html.twig');
    }

    /**
     * Formulaire pour modifier les informations du profil.
     */
    #[Route('/edition', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, EntityManagerInterface $entityManager): Response
    {
        /** @var User $user */
        $user = $this->getUser(); // Récupère l'utilisateur connecté

        $form = $this->createForm(ProfileType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Si le formulaire est valide, on enregistre en base de données
            $entityManager->flush();
            $this->addFlash('success', 'Vos informations ont été mises à jour avec succès.');

            return $this->redirectToRoute('app_account_index');
        }

        return $this->render('account/edit.html.twig', [
            'form' => $form,
        ]);
    }

    /**
     * Modification du mot de passe.
     */
    #[Route('/mot-de-passe', name: 'password', methods: ['GET', 'POST'])]
    public function password(Request $request, EntityManagerInterface $entityManager, UserPasswordHasherInterface $passwordHasher): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        $form = $this->createForm(ChangePasswordType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Récupère le mot de passe en clair et le crypte avant la sauvegarde
            $newPassword = $form->get('plainPassword')->getData();
            $hashedPassword = $passwordHasher->hashPassword($user, $newPassword);
            $user->setPassword($hashedPassword);

            $entityManager->flush();
            $this->addFlash('success', 'Votre mot de passe a été mis à jour avec succès.');

            return $this->redirectToRoute('app_account_index');
        }

        return $this->render('account/password.html.twig', [
            'form' => $form,
        ]);
    }

    /**
     * Suppression de son compte.
     */
    #[Route('/suppression', name: 'delete', methods: ['POST'])]
    public function delete(Request $request, EntityManagerInterface $entityManager): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        // Check du jeton CSRF pour éviter les suppressions accidentelles via un lien externe
        if ($this->isCsrfTokenValid('delete_account'.$user->getId(), (string) $request->request->get('_token'))) {
            // Vide la session et le token de sécurité avant de supprimer l'utilisateur
            $request->getSession()->invalidate();
            $this->container->get('security.token_storage')->setToken(null);

            $entityManager->remove($user);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_home');
    }

    /**
     * Liste des commandes de l'utilisateur.
     */
    #[Route('/mes-commandes', name: 'orders')]
    public function orders(OrderRepository $orderRepository): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        // Récupère les commandes du user, de la plus récente à la plus ancienne
        $orders = $orderRepository->findBy(
            ['user' => $user],
            ['createdAt' => 'DESC']
        );

        return $this->render('account/orders.html.twig', [
            'orders' => $orders,
        ]);
    }

    /**
     * Liste d'une commande spécifique de l'utilisateur.
     */
    #[Route('/mes-commandes/{reference}', name: 'order_show')]
    public function show(string $reference, OrderRepository $orderRepository, OrderItemRepository $orderItemRepository): Response
    {
        $user = $this->getUser();
        $order = $orderRepository->findOneBy(['reference' => $reference]);

        // Si la commande n'existe pas ou n'appartient pas à ce user
        if (!$order || $order->getUser() !== $user) {
            $this->addFlash('danger', 'Vous n\'avez pas accès à cette commande.');

            return $this->redirectToRoute('app_account_orders');
        }

        $items = $orderItemRepository->findBy(['purchase' => $order]);

        return $this->render('account/order_show.html.twig', [
            'order' => $order,
            'items' => $items,
        ]);
    }

    /**
     * Génère la facture en PDF.
     */
    #[Route('/mes-commandes/{reference}/facture', name: 'order_pdf')]
    public function invoice(string $reference, OrderRepository $orderRepository, OrderItemRepository $orderItemRepository): Response
    {
        $user = $this->getUser();
        $order = $orderRepository->findOneBy(['reference' => $reference]);

        if (!$order || $order->getUser() !== $user) {
            $this->addFlash('danger', 'Vous n\'avez pas accès à cette facture.');

            return $this->redirectToRoute('app_account_orders');
        }

        $items = $orderItemRepository->findBy(['purchase' => $order]);

        // Configuration de Dompdf
        $pdfOptions = new Options();
        $pdfOptions->set('defaultFont', 'Arial');
        $pdfOptions->set('isRemoteEnabled', true); // Charge les images et le CSS

        $dompdf = new Dompdf($pdfOptions);

        $html = $this->renderView('account/pdf_invoice.html.twig', [
            'order' => $order,
            'items' => $items,
        ]);

        // Transforme mon fichier Twig en HTML pur
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        $pdfContent = $dompdf->output();

        // Renvoie le contenu en précisant que c'est un PDF à télécharger
        return new Response($pdfContent, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="Facture_TechFlow_'.$order->getReference().'.pdf"',
        ]);
    }
}
