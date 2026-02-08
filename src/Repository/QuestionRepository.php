<?php

namespace App\Repository;

use App\Entity\Question;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Question>
 */
class QuestionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Question::class);
    }

    /**
     * Trouver toutes les questions d'un quiz, ordonnées
     */
    public function findByQuizOrdered(int $quizId): array
    {
        return $this->createQueryBuilder('q')
            ->andWhere('q.quiz = :quizId')
            ->setParameter('quizId', $quizId)
            ->orderBy('q.ordre', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Compter le nombre de questions d'un quiz
     */
    public function countByQuiz(int $quizId): int
    {
        return $this->createQueryBuilder('q')
            ->select('COUNT(q.id)')
            ->andWhere('q.quiz = :quizId')
            ->setParameter('quizId', $quizId)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Calculer le total de points d'un quiz
     */
    public function getTotalPointsByQuiz(int $quizId): int
    {
        $result = $this->createQueryBuilder('q')
            ->select('SUM(q.points)')
            ->andWhere('q.quiz = :quizId')
            ->setParameter('quizId', $quizId)
            ->getQuery()
            ->getSingleScalarResult();

        return $result ? (int) $result : 0;
    }
    public function countQuestionsValidesByQuiz(int $quizId): int
{
    return $this->createQueryBuilder('q')
        ->select('COUNT(DISTINCT q.id)')
        ->join('q.reponses', 'r')
        ->andWhere('q.quiz = :quizId')
        ->andWhere('r.estCorrecte = true')
        ->setParameter('quizId', $quizId)
        ->getQuery()
        ->getSingleScalarResult();
}

}
