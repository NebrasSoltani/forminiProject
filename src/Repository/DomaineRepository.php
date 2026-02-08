<?php

namespace App\Repository;

use App\Entity\Domaine;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Domaine>
 */
class DomaineRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Domaine::class);
    }

    /**
     * @return Domaine[] Returns an array of Domaine objects
     */
    public function findAllOrderedByName(): array
    {
        return $this->createQueryBuilder('d')
            ->orderBy('d.nom', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findOneByNom(string $nom): ?Domaine
    {
        return $this->createQueryBuilder('d')
            ->andWhere('d.nom = :nom')
            ->setParameter('nom', $nom)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
