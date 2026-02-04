<?php

namespace App\Repository;

use App\Entity\Reponse;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Reponse>
 */
class ReponseRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Reponse::class);
    }

    /**
     * Trouver toutes les réponses d'une question
     */
    public function findByQuestion(int $questionId): array
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.question = :questionId')
            ->setParameter('questionId', $questionId)
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouver les réponses correctes d'une question
     */
    public function findCorrectByQuestion(int $questionId): array
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.question = :questionId')
            ->andWhere('r.estCorrecte = true')
            ->setParameter('questionId', $questionId)
            ->getQuery()
            ->getResult();
    }
}