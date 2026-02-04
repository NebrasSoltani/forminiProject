<?php

namespace App\Repository;

use App\Entity\Quiz;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Quiz>
 */
class QuizRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Quiz::class);
    }

    /**
     * Trouver tous les quiz d'une formation
     */
    public function findByFormation(int $formationId): array
    {
        return $this->createQueryBuilder('q')
            ->andWhere('q.formation = :formationId')
            ->setParameter('formationId', $formationId)
            ->orderBy('q.id', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Compter le nombre de quiz d'une formation
     */
    public function countByFormation(int $formationId): int
    {
        return $this->createQueryBuilder('q')
            ->select('COUNT(q.id)')
            ->andWhere('q.formation = :formationId')
            ->setParameter('formationId', $formationId)
            ->getQuery()
            ->getSingleScalarResult();
    }
}