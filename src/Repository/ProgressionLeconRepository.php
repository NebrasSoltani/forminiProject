<?php

namespace App\Repository;

use App\Entity\ProgressionLecon;
use App\Entity\User;
use App\Entity\Lecon;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ProgressionLecon>
 */
class ProgressionLeconRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ProgressionLecon::class);
    }

    /**
     * Trouver la progression d'un apprenant pour une leçon
     */
    public function findOneByApprenantAndLecon(User $apprenant, Lecon $lecon): ?ProgressionLecon
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.apprenant = :apprenant')
            ->andWhere('p.lecon = :lecon')
            ->setParameter('apprenant', $apprenant)
            ->setParameter('lecon', $lecon)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Compter le nombre de leçons terminées par un apprenant pour une formation
     */
    public function countLeconTermineesParFormation(User $apprenant, int $formationId): int
    {
        return $this->createQueryBuilder('p')
            ->select('COUNT(p.id)')
            ->innerJoin('p.lecon', 'l')
            ->andWhere('p.apprenant = :apprenant')
            ->andWhere('l.formation = :formationId')
            ->andWhere('p.terminee = true')
            ->setParameter('apprenant', $apprenant)
            ->setParameter('formationId', $formationId)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Vérifier si toutes les leçons d'une formation sont terminées
     */
    public function toutesLeconsTerminees(User $apprenant, int $formationId, int $totalLecons): bool
    {
        $leconsTerminees = $this->countLeconTermineesParFormation($apprenant, $formationId);
        return $leconsTerminees >= $totalLecons;
    }
}
