<?php

namespace App\Tests\Entity;

use App\Entity\User;
use PHPUnit\Framework\TestCase;

class UserTest extends TestCase
{
    /**
     * Vérifie que la logique de base de Symfony Security est bien respectée.
     */
    public function testUserRolesDefault(): void
    {
        $user = new User();

        // Tout utilisateur doir avoir le ROLE_USER
        $this->assertContains('ROLE_USER', $user->getRoles());
    }

    public function testUserEmailAndIdentifier(): void
    {
        $user = new User();
        $user->setEmail('mounir@techflow.com');

        // Getter classique
        $this->assertSame('mounir@techflow.com', $user->getEmail());

        // getUserIdentifier() doit renvoyer l'email pour le login
        $this->assertSame('mounir@techflow.com', $user->getUserIdentifier());
    }
}
