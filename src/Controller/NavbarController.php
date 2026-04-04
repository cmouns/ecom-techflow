<?php

namespace App\Controller;

use App\Repository\CategoryRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

class NavbarController extends AbstractController
{
    /**
     * Récupère les catégories pour les afficher dans le menu de navigation.
     */
    public function categories(CategoryRepository $categoryRepository): Response
    {
        // Récupère toutes les catégories pour générer les liens du menu déroulant
        $categories = $categoryRepository->findAll();

        return $this->render('navbar/_categories.html.twig', [
            'categories' => $categories,
        ]);
    }
}
