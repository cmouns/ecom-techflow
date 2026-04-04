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
        // Récupère les paramètres de l'URL pour les filtres et le tri
        $categorySlug = $request->query->get('category');
        $sort = $request->query->get('sort_by', 'new');

        // Prépare ma requête personnalisée avec le QueryBuilder
        $qb = $productRepository->createQueryBuilder('p');

        // Filtre les produits au choix d'une catégorie
        if ($categorySlug) {
            $category = $categoryRepository->findOneBy(['slug' => $categorySlug]);
            if ($category) {
                // Lie les produits à la catégorie trouvée
                $qb->andWhere('p.category = :category')
                   ->setParameter('category', $category);
            }
        }

        // Gestion de la logique de tri
        if ('price_asc' === $sort) {
            $qb->orderBy('p.price', 'ASC');
        } elseif ('price_desc' === $sort) {
            $qb->orderBy('p.price', 'DESC');
        } else {
            // Trie par id pour voir les derniers ajouts
            $qb->orderBy('p.id', 'DESC');
        }

        // Utilise le bundle KnpPaginator pour limiter à 12 produits par page
        $products = $paginator->paginate(
            $qb->getQuery(), // Ma requête préparée
            $request->query->getInt('page', 1), // Numéro de la page actuelle
            12 // Nombre d'éléments par page
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
