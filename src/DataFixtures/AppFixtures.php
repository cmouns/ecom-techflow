<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\Category;
use App\Entity\Product;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    public function __construct(private readonly UserPasswordHasherInterface $passwordHasher)
    {
    }

    public function load(ObjectManager $manager): void
    {
        $admin = (new User())
            ->setEmail('admin@ecommerce.local')
            ->setFirstName('Admin')
            ->setLastName('System')
            ->setRoles(['ROLE_ADMIN']);

        $admin->setPassword($this->passwordHasher->hashPassword($admin, 'password'));
        $admin->setCreatedAt(new \DateTimeImmutable());
        $manager->persist($admin);

        $faker = Factory::create('fr_FR');

        $categoryNames = ['Claviers Mécaniques', 'Écrans Ultra-Larges', 'Mobilier Ergonomique'];
        $categories = [];

        foreach ($categoryNames as $name) {
            $category = (new Category())
                ->setName($name)
                ->setSlug(strtolower(str_replace([' ', 'É'], ['-', 'e'], $name)));

            $manager->persist($category);
            $categories[] = $category;
        }

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
