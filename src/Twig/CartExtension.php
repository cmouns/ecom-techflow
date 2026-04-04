<?php

namespace App\Twig;

use App\Service\CartService;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class CartExtension extends AbstractExtension
{
    // Injecte mon service panier pour pouvoir utiliser ses méthodes dans mes vues
    public function __construct(
        private CartService $cartService,
    ) {
    }

    /**
     * Déclare de nouvelles fonctions utilisables directement dans les fichiers .html.twig.
     *
     * * @return TwigFunction[]
     */
    public function getFunctions(): array
    {
        return [
            // Crée une fonction Twig "cart_count" qui va exécuter la méthode "getQuantityCount" de mon service
            new TwigFunction('cart_count', [$this->cartService, 'getQuantityCount']),
        ];
    }
}
