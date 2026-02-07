<?php

namespace App\Repository;

use App\Entity\Evenement;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Evenement>
 */
class EvenementRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Evenement::class);
    }

    /**
     * Retourne les événements à venir
     */
    public function findUpcomingEvents()
    {
        return $this->createQueryBuilder('e')
            ->where('e.dateDebut >= :now')
            ->andWhere('e.isActif = :actif')
            ->setParameter('now', new \DateTime())
            ->setParameter('actif', true)
            ->orderBy('e.dateDebut', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Retourne les événements par type
     */
    public function findByType(string $type)
    {
        return $this->createQueryBuilder('e')
            ->where('e.type = :type')
            ->andWhere('e.isActif = :actif')
            ->setParameter('type', $type)
            ->setParameter('actif', true)
            ->orderBy('e.dateDebut', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Retourne les événements actifs
     */
    public function findActiveEvents()
    {
        return $this->createQueryBuilder('e')
            ->where('e.isActif = :actif')
            ->setParameter('actif', true)
            ->orderBy('e.dateDebut', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
