<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class PageControllerTest extends WebTestCase
{
    /**
     * Vérifie que la page principale du site ne plante pas.
     */
    public function testHomePageIsAccessible(): void
    {
        $client = static::createClient();

        $client->request('GET', '/');

        $this->assertResponseIsSuccessful();
    }

    public function testLoginPageIsAccessible(): void
    {
        $client = static::createClient();

        $client->request('GET', '/login');

        // La page charge bien
        $this->assertResponseIsSuccessful();

        // Vérifie qu'il y a bien une balise <form> dans le HTML généré
        $this->assertSelectorExists('form');
    }

    public function testAccountPageIsProtected(): void
    {
        $client = static::createClient();
        $client->request('GET', '/compte/');

        // Vérifie la restriction d'accès sur l'espace client
        $this->assertResponseRedirects('/login');
    }
}
