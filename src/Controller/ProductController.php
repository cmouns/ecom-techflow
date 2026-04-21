<?php

namespace App\Controller;

use App\Repository\CategoryRepository;
use App\Repository\ProductRepository;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ProductController extends AbstractController
{
    /**
     * Affiche le catalogue des produits avec filtres et pagination.
     */
    #[Route('/products', name: 'app_product_index')]
    public function index(
        ProductRepository $productRepository,
        CategoryRepository $categoryRepository,
        Request $request,
        PaginatorInterface $paginator,
    ): Response {
        $categorySlug = $request->query->get('category');
        $sort = $request->query->get('sort_by', 'new');

        $category = null;
        if ($categorySlug) {
            $category = $categoryRepository->findOneBy(['slug' => $categorySlug]);
        }
        // Appel de la requête personnalisée
        $query = $productRepository->findFiltered($category, $sort);

        $products = $paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            12
        );

        return $this->render('product/index.html.twig', [
            'products' => $products,
        ]);
    }

    /**
     * Affiche la fiche détaillée d'un produit.
     */
    #[Route('/product/{slug}', name: 'app_product_show')]
    public function showone(string $slug, ProductRepository $productRepository): Response
    {
        // Cherche le produit en utilisant son slug
        $product = $productRepository->findOneBy(['slug' => $slug]);

        // Erreur 404 si le produit n'existe pas en base
        if (!$product) {
            throw $this->createNotFoundException('Ce produit n\'existe pas.');
        }

        return $this->render('product/show.html.twig', [
            'product' => $product,
        ]);
    }
}
