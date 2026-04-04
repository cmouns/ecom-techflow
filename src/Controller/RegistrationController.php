<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\RegistrationFormType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

class RegistrationController extends AbstractController
{
    /**
     * Gère l'inscription d'un nouvel utilisateur sur le site.
     */
    #[Route('/register', name: 'app_register')]
    public function register(
        Request $request,
        UserPasswordHasherInterface $userPasswordHasher,
        Security $security,
        EntityManagerInterface $entityManager,
    ): Response {
        $user = new User();
        // Création du formulaire d'inscription
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Récupère le mot de passe en clair saisi dans le formulaire
            /** @var string $plainPassword */
            $plainPassword = $form->get('plainPassword')->getData();

            // Hash le mot de passe avant de l'enregistrer en base
            $user->setPassword($userPasswordHasher->hashPassword($user, $plainPassword));

            // Demande à Doctrine de sauvegarder le nouvel utilisateur
            $entityManager->persist($user);
            $entityManager->flush();

            // Message flash pour confirmer que ça a marché
            $this->addFlash('success', 'Votre compte a été créé avec succès !');

            // Une fois inscrit, on connecte automatiquement l'utilisateur
            $security->login($user, 'form_login', 'main');

            // Redirige vers la page d'accueil
            return $this->redirectToRoute('app_home');
        }

        return $this->render('registration/register.html.twig', [
            'registrationForm' => $form,
        ]);
    }
}
