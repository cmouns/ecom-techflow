<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class AdminAccessTest extends WebTestCase
{
    /**
     * Vérifie que le pare-feu bloque bien les accès anonymes.
     */
    public function testAdminPanelIsProtected(): void
    {
        // Crée un client HTTP (simulation)
        $client = static::createClient();

        // Tente d'accéder à une zone sécurisée sans être connecté
        $client->request('GET', '/admin/product/');

        // Vérifie que le framework nous rejette et nous renvoie au login
        $this->assertResponseRedirects('/login');
    }

    /**
     * Vérifie qu'une page publique est bien accessible à tous.
     */
    public function testCartPageIsAccessible(): void
    {
        $client = static::createClient();

        // Visite la page du panier
        $client->request('GET', '/cart/');

        // Attend un code HTTP 200
        $this->assertResponseIsSuccessful();
    }
}
