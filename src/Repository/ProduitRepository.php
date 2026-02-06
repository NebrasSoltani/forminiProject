<?php

namespace App\Repository;

use App\Entity\Produit;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Produit>
 */
class ProduitRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Produit::class);
    }

    public function findDisponibles(): array
    {
        return $this->createQueryBuilder('p')
            ->where('p.statut = :statut')
            ->setParameter('statut', 'actif')
            ->orderBy('p.dateCreation', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findByCategorie(string $categorie): array
    {
        return $this->createQueryBuilder('p')
            ->where('p.categorie = :categorie')
            ->andWhere('p.statut = :statut')
            ->setParameter('categorie', $categorie)
            ->setParameter('statut', 'actif')
            ->orderBy('p.dateCreation', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
