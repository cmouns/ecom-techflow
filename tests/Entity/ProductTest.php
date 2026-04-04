<?php

namespace App\Tests\Entity;

use App\Entity\Product;
use PHPUnit\Framework\TestCase;

class ProductTest extends TestCase
{
    public function testProductGettersAndSetters(): void
    {
        // Préparation des données de test
        $product = new Product();
        $product->setName('Écran 4K');
        $product->setPrice(19099);
        $product->setStock(10);

        // Action et Vérification
        $this->assertSame('Écran 4K', $product->getName());
        $this->assertSame(19099, $product->getPrice()); // Vérifie bien le prix en centimes
        $this->assertSame(10, $product->getStock());
    }

    /**
     * Vérifie le comportement du constructeur de l'entité.
     */
    public function testProductDefaultValues(): void
    {
        $product = new Product();

        // S'assure que la date de création se génère toute seule
        $this->assertInstanceOf(\DateTimeImmutable::class, $product->getCreatedAt());

        // Vérifie que les collections Doctrine ne sont pas nulles, mais bien des tableaux/collections vides
        $this->assertCount(0, $product->getProductImages());
        $this->assertCount(0, $product->getOrderItems());
    }
}
