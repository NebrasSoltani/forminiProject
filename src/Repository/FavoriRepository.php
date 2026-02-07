<?php

namespace App\Repository;

use App\Entity\Favori;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class FavoriRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Favori::class);
    }

    public function findByApprenant(User $apprenant): array
    {
        return $this->createQueryBuilder('f')
            ->andWhere('f.apprenant = :apprenant')
            ->setParameter('apprenant', $apprenant)
            ->orderBy('f.dateAjout', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findOneByApprenantAndFormation(User $apprenant, int $formationId): ?Favori
    {
        return $this->createQueryBuilder('f')
            ->andWhere('f.apprenant = :apprenant')
            ->andWhere('f.formation = :formationId')
            ->setParameter('apprenant', $apprenant)
            ->setParameter('formationId', $formationId)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function isFavori(User $apprenant, int $formationId): bool
    {
        return $this->findOneByApprenantAndFormation($apprenant, $formationId) !== null;
    }
}