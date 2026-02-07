<?php

namespace App\Repository;

use App\Entity\Lecon;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Lecon>
 */
class LeconRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Lecon::class);
    }

    /**
     * Trouver toutes les leçons d'une formation, ordonnées
     */
    public function findByFormationOrdered(int $formationId): array
    {
        return $this->createQueryBuilder('l')
            ->andWhere('l.formation = :formationId')
            ->setParameter('formationId', $formationId)
            ->orderBy('l.ordre', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Compter le nombre de leçons d'une formation
     */
    public function countByFormation(int $formationId): int
    {
        return $this->createQueryBuilder('l')
            ->select('COUNT(l.id)')
            ->andWhere('l.formation = :formationId')
            ->setParameter('formationId', $formationId)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Calculer la durée totale des leçons d'une formation
     */
    public function getTotalDureeByFormation(int $formationId): int
    {
        $result = $this->createQueryBuilder('l')
            ->select('SUM(l.duree)')
            ->andWhere('l.formation = :formationId')
            ->setParameter('formationId', $formationId)
            ->getQuery()
            ->getSingleScalarResult();

        return $result ? (int) $result : 0;
    }
}