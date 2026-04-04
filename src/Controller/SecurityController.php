<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class SecurityController extends AbstractController
{
    /**
     * Affiche et gère le formulaire de connexion.
     */
    #[Route(path: '/login', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        // Si la personne est déjà connectée,
        // ça ne sert à rien de lui afficher le formulaire, on la renvoie à l'accueil.
        if ($this->getUser()) {
            return $this->redirectToRoute('app_home');
        }

        // Intercepte les potentielles erreurs de connexion
        $error = $authenticationUtils->getLastAuthenticationError();

        // Garde en mémoire le dernier email saisi pour que l'utilisateur
        // n'ait pas à le retaper s'il s'est trompé de mot de passe
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('security/login.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error,
        ]);
    }

    /**
     * Gère la déconnexion de l'utilisateur.
     */
    #[Route(path: '/logout', name: 'app_logout')]
    public function logout(): void
    {
        throw new \LogicException('Cette méthode peut rester vide - elle sera interceptée par la clé de déconnexion de votre pare-feu.');
    }
}
