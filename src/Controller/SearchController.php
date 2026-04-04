<?php

namespace App\Controller;

use App\Repository\ProductRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class SearchController extends AbstractController
{
    /**
     * Gère les résultats de la barre de recherche globale.
     */
    #[Route('/search', name: 'app_search', methods: ['GET'])]
    public function index(Request $request, ProductRepository $productRepository): Response
    {
        // Récupère le terme recherché dans l'URL
        // Le string force le type pour éviter les erreurs si quelqu'un bricole l'URL
        $query = (string) $request->query->get('q', '');

        $products = [];

        // Sollicite la base de données que si l'utilisateur a tapé quelque chose
        if ('' !== $query) {
            $products = $productRepository->searchByName($query);
        }

        return $this->render('search/index.html.twig', [
            'products' => $products,
            'query' => $query, // Renvoie le mot tapé pour le réafficher dans la barre de recherche ou le titre
        ]);
    }
}
