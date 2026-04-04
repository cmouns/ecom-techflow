<?php

namespace App\Repository;

use App\Entity\Product;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Product>
 */
class ProductRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Product::class);
    }

    /**
     * Recherche des produits dont le nom contient le mot-clé saisi.
     *
     * @return Product[] Retourne un tableau d'objets Product
     */
    public function searchByName(string $search): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.name LIKE :val')
            // Ajoute les '%' ici et utilise setParameter pour protéger des injections SQL
            ->setParameter('val', '%'.$search.'%')
            // Affiche les produits les plus récents en premier
            ->orderBy('p.createdAt', 'DESC')
            // Limite à 20 résultats max pour ne pas ralentir le serveur
            ->setMaxResults(20)
            ->getQuery()
            ->getResult();
    }
}
