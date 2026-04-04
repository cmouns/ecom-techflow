<?php

namespace App\Controller;

use App\Repository\ProductRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class HomeController extends AbstractController
{
    /**
     * Affiche la page d'accueil du site.
     */
    #[Route('/', name: 'app_home')]
    public function index(ProductRepository $productRepository): Response
    {
        // Récupère les 4 derniers produits ajoutés en base de données
        // Trie par date de création décroissante
        $latestProducts = $productRepository->findBy([], ['createdAt' => 'DESC'], 4);

        return $this->render('home/index.html.twig', [
            'products' => $latestProducts,
        ]);
    }
}
