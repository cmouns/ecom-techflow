<?php

namespace App\Controller;

use App\Service\CartService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/cart', name: 'app_cart_')]
class CartController extends AbstractController
{
    /**
     * Affiche le contenu du panier et le total.
     */
    #[Route('/', name: 'index')]
    public function index(CartService $cartService): Response
    {
        // Demande au service de nous donner les produits complets et le calcul du total
        return $this->render('cart/index.html.twig', [
            'items' => $cartService->getFullCart(),
            'total' => $cartService->getTotal(),
        ]);
    }

    /**
     * Ajoute un produit au panier.
     */
    #[Route('/add/{id}', name: 'add', methods: ['POST'])]
    public function add(int $id, Request $request, CartService $cartService): Response
    {
        // Récupère la quantité choisie par l'utilisateur
        $quantity = (int) $request->request->get('quantity', 1);

        // Délègue l'ajout au service
        $cartService->add($id, $quantity);

        $this->addFlash('success', 'Produit ajouté au panier !');

        return $this->redirectToRoute('app_cart_index');
    }

    /**
     * Modifie la quantité d'un produit directement depuis le panier.
     */
    #[Route('/update/{id}', name: 'update', methods: ['POST'])]
    public function update(int $id, Request $request, CartService $cartService): Response
    {
        // Récupère la nouvelle quantité envoyée par le formulaire de modification
        $quantity = (int) $request->request->get('quantity', 0);
        $cartService->setQuantity($id, $quantity);

        return $this->redirectToRoute('app_cart_index');
    }

    /**
     * Supprime un produit du panier.
     */
    #[Route('/remove/{id}', name: 'remove')]
    public function remove(int $id, CartService $cartService): Response
    {
        // Suppression de la ligne du produit concerné
        $cartService->remove($id);

        return $this->redirectToRoute('app_cart_index');
    }

    /**
     * Vide complètement le panier.
     */
    #[Route('/empty', name: 'empty')]
    public function empty(CartService $cartService): Response
    {
        $cartService->empty();

        return $this->redirectToRoute('app_cart_index');
    }
}
