<?php

namespace App\Repository;

use App\Entity\Candidature;
use App\Entity\OffreStage;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Candidature>
 */
class CandidatureRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Candidature::class);
    }

    public function findByApprenant(User $apprenant): array
    {
        return $this->createQueryBuilder('c')
            ->where('c.apprenant = :apprenant')
            ->setParameter('apprenant', $apprenant)
            ->orderBy('c.dateCandidature', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findByOffre(OffreStage $offre): array
    {
        return $this->createQueryBuilder('c')
            ->where('c.offreStage = :offre')
            ->setParameter('offre', $offre)
            ->orderBy('c.dateCandidature', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function hasAlreadyApplied(User $apprenant, OffreStage $offre): bool
    {
        $count = $this->createQueryBuilder('c')
            ->select('COUNT(c.id)')
            ->where('c.apprenant = :apprenant')
            ->andWhere('c.offreStage = :offre')
            ->setParameter('apprenant', $apprenant)
            ->setParameter('offre', $offre)
            ->getQuery()
            ->getSingleScalarResult();

        return $count > 0;
    }
}
