<?php

namespace App\Repository;

use App\Entity\OffreStage;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<OffreStage>
 */
class OffreStageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, OffreStage::class);
    }

    public function findPubliees(): array
    {
        return $this->createQueryBuilder('o')
            ->where('o.statut = :statut')
            ->setParameter('statut', 'publiee')
            ->orderBy('o.datePublication', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findBySociete(User $societe): array
    {
        return $this->createQueryBuilder('o')
            ->where('o.societe = :societe')
            ->setParameter('societe', $societe)
            ->orderBy('o.datePublication', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findByType(string $type): array
    {
        return $this->createQueryBuilder('o')
            ->where('o.typeStage = :type')
            ->andWhere('o.statut = :statut')
            ->setParameter('type', $type)
            ->setParameter('statut', 'publiee')
            ->orderBy('o.datePublication', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
