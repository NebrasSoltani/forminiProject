<?php

namespace App\Repository;

use App\Entity\Inscription;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class InscriptionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Inscription::class);
    }

    public function findByApprenant(User $apprenant): array
    {
        return $this->createQueryBuilder('i')
            ->andWhere('i.apprenant = :apprenant')
            ->setParameter('apprenant', $apprenant)
            ->orderBy('i.dateInscription', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findOneByApprenantAndFormation(User $apprenant, int $formationId): ?Inscription
    {
        return $this->createQueryBuilder('i')
            ->andWhere('i.apprenant = :apprenant')
            ->andWhere('i.formation = :formationId')
            ->setParameter('apprenant', $apprenant)
            ->setParameter('formationId', $formationId)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function isInscrit(User $apprenant, int $formationId): bool
    {
        return $this->findOneByApprenantAndFormation($apprenant, $formationId) !== null;
    }
}