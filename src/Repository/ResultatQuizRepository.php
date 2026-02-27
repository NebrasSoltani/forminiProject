<?php

namespace App\Repository;

use App\Entity\ResultatQuiz;
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
     * Vérifier si un apprenant a déjà complété un quiz
     */
    public function hasApprenantCompletedQuiz($apprenant, $quiz): bool
    {
        return $this->createQueryBuilder('r')
            ->select('COUNT(r.id)')
            ->andWhere('r.apprenant = :apprenant')
            ->andWhere('r.quiz = :quiz')
            ->setParameter('apprenant', $apprenant)
            ->setParameter('quiz', $quiz)
            ->getQuery()
            ->getSingleScalarResult() > 0;
    }

    /**
     * Obtenir le résultat d'un apprenant pour un quiz
     */
    public function findByApprenantAndQuiz($apprenant, $quiz): ?ResultatQuiz
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.apprenant = :apprenant')
            ->andWhere('r.quiz = :quiz')
            ->setParameter('apprenant', $apprenant)
            ->setParameter('quiz', $quiz)
            ->getQuery()
            ->getOneOrNullResult();
    }
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?ResultatQuiz
    //    {
    //        return $this->createQueryBuilder('r')
    //            ->andWhere('r.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
