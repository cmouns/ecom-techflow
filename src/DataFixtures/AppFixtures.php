<?php

namespace App\DataFixtures;

use App\Entity\Category;
use App\Entity\Product;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    /**
     * Injection de l'outil de hachage et mes variables d'environnement secrètes.
     */
    public function __construct(
        private readonly UserPasswordHasherInterface $passwordHasher,
        #[Autowire('%env(ADMIN_EMAIL)%')] private readonly string $adminEmail,
        #[Autowire('%env(ADMIN_PASSWORD)%')] private readonly string $adminPassword,
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        // Création de l'admin avec les données cachées dans le .env
        $admin = (new User())
            ->setEmail($this->adminEmail) // Utilise la variable sécurisée
            ->setFirstName('Mounir')
            ->setLastName('Admin')
            ->setRoles(['ROLE_ADMIN']);

        // Hache le mot de passe qui vient du .env
        $admin->setPassword($this->passwordHasher->hashPassword($admin, $this->adminPassword));
        $admin->setCreatedAt(new \DateTimeImmutable());

        $manager->persist($admin);

        // Initialisation de Faker
        $faker = Factory::create('fr_FR');

        // Création des catégories
        $categoryNames = ['Claviers Mécaniques', 'Écrans Ultra-Larges', 'Mobilier Ergonomique'];
        $categories = [];

        foreach ($categoryNames as $name) {
            $category = (new Category())
                ->setName($name)
                ->setSlug(strtolower(str_replace([' ', 'É'], ['-', 'e'], $name)));

            $manager->persist($category);
            $categories[] = $category;
        }

        // Génération de 50 produits
        for ($i = 0; $i < 50; ++$i) {
            $product = (new Product())
                ->setName('TechFlow '.ucfirst($faker->word()))
                ->setSlug($faker->slug())
                ->setDescription($faker->realText(200))
                ->setPrice($faker->numberBetween(7500, 150000))
                ->setStock($faker->numberBetween(0, 30))
                ->setCategory($faker->randomElement($categories))
                ->setCreatedAt(new \DateTimeImmutable());

            $manager->persist($product);
        }

        $manager->flush();
    }
}
