<?php

namespace App\Service;

use App\Repository\ProductRepository;
use Symfony\Component\HttpFoundation\RequestStack;

class CartService
{
    // Utilise la promotion de propriétés du constructeur pour injecter mes dépendances
    public function __construct(
        private RequestStack $requestStack,
        private ProductRepository $productRepository,
    ) {
    }

    /**
     * Ajoute un produit au panier ou incrémente sa quantité s'il y est déjà.
     */
    public function add(int $id, int $quantity = 1): void
    {
        $product = $this->productRepository->find($id);
        if (!$product) {
            return;
        }
        // On passe par le RequestStack pour accéder à la session de l'utilisateur
        $session = $this->requestStack->getSession();
        $cart = $session->get('cart', []);

        // On calcule combien il en a déjà dans le panier
        $currentQuantity = empty($cart[$id]) ? 0 : $cart[$id];

        if (($currentQuantity + $quantity) > $product->getStock()) {
            throw new \LogicException('Stock insuffisant pour ce produit.');
        }

        $cart[$id] = $currentQuantity + $quantity;

        // Sauvegarde le nouveau tableau dans la session
        $session->set('cart', $cart);
    }

    /**
     * Définit une quantité précise pour un article du panier.
     */
    public function setQuantity(int $id, int $quantity): void
    {
        $session = $this->requestStack->getSession();
        $cart = $session->get('cart', []);

        // Retire l'article si le client met la quantité à 0 ou en négatif
        if ($quantity <= 0) {
            $this->remove($id);

            return;
        }

        $cart[$id] = $quantity;
        $session->set('cart', $cart);
    }

    /**
     * Supprime totalement une ligne du panier.
     */
    public function remove(int $id): void
    {
        $session = $this->requestStack->getSession();
        $cart = $session->get('cart', []);

        if (!empty($cart[$id])) {
            unset($cart[$id]); // Détruit l'entrée du tableau correspondante
        }

        $session->set('cart', $cart);
    }

    /**
     * Vide intégralement le panier.
     */
    public function empty(): void
    {
        // Supprime la clé 'cart' de la session
        $this->requestStack->getSession()->remove('cart');
    }

    /**
     * Récupère le panier complet.
     *
     * @return array<int, array{product: \App\Entity\Product, quantity: int}>
     */
    public function getFullCart(): array
    {
        $session = $this->requestStack->getSession();
        $cart = $session->get('cart', []);
        $fullCart = [];

        foreach ($cart as $id => $quantity) {
            $product = $this->productRepository->find($id);

            // Si le produit existe en base, on l'ajoute
            if ($product) {
                $fullCart[] = ['product' => $product, 'quantity' => $quantity];
            } else {
                // S'il a été supprimé de la base entre-temps, on le retire du panier du client
                $this->remove($id);
            }
        }

        return $fullCart;
    }

    /**
     * Calcule le montant total du panier.
     */
    public function getTotal(): int
    {
        $total = 0;

        foreach ($this->getFullCart() as $item) {
            // Multiplie le prix unitaire par la quantité
            $total += $item['product']->getPrice() * $item['quantity'];
        }

        return $total;
    }

    /**
     * Calcule le nombre total d'articles.
     */
    public function getQuantityCount(): int
    {
        $session = $this->requestStack->getSession();
        $cart = $session->get('cart', []);

        $count = 0;
        foreach ($cart as $quantity) {
            $count += $quantity;
        }

        return $count;
    }
}
