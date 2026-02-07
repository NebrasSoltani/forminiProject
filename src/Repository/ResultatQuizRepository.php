<?php

namespace App\Repository;

use App\Entity\ResultatQuiz;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ResultatQuiz>
 */
class ResultatQuizRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ResultatQuiz::class);
    }

    /**
     * Trouver tous les résultats d'un apprenant pour un quiz
     */
    public function findByApprenantAndQuiz(User $apprenant, int $quizId): array
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.apprenant = :apprenant')
            ->andWhere('r.quiz = :quizId')
            ->setParameter('apprenant', $apprenant)
            ->setParameter('quizId', $quizId)
            ->orderBy('r.dateTentative', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouver le meilleur résultat d'un apprenant pour un quiz
     */
    public function findBestByApprenantAndQuiz(User $apprenant, int $quizId): ?ResultatQuiz
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.apprenant = :apprenant')
            ->andWhere('r.quiz = :quizId')
            ->setParameter('apprenant', $apprenant)
            ->setParameter('quizId', $quizId)
            ->orderBy('r.note', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Trouver tous les résultats d'un quiz (pour le formateur)
     */
    public function findByQuiz(int $quizId): array
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.quiz = :quizId')
            ->setParameter('quizId', $quizId)
            ->orderBy('r.dateTentative', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Calculer la note moyenne d'un quiz
     */
    public function getAverageNoteByQuiz(int $quizId): float
    {
        $result = $this->createQueryBuilder('r')
            ->select('AVG(r.note)')
            ->andWhere('r.quiz = :quizId')
            ->setParameter('quizId', $quizId)
            ->getQuery()
            ->getSingleScalarResult();

        return $result ? (float) $result : 0.0;
    }

    /**
     * Compter le nombre de tentatives pour un quiz
     */
    public function countByQuiz(int $quizId): int
    {
        return $this->createQueryBuilder('r')
            ->select('COUNT(r.id)')
            ->andWhere('r.quiz = :quizId')
            ->setParameter('quizId', $quizId)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Compter le nombre de réussites pour un quiz
     */
    public function countSuccessByQuiz(int $quizId): int
    {
        return $this->createQueryBuilder('r')
            ->select('COUNT(r.id)')
            ->andWhere('r.quiz = :quizId')
            ->andWhere('r.reussi = true')
            ->setParameter('quizId', $quizId)
            ->getQuery()
            ->getSingleScalarResult();
    }
}