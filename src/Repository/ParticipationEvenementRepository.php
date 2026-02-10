<?php

namespace App\Repository;

use App\Entity\Evenement;
use App\Entity\User;
use App\Entity\ParticipationEvenement;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ParticipationEvenement>
 */
class ParticipationEvenementRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ParticipationEvenement::class);
    }

    public function isParticipant(User $user, Evenement $evenement): bool
    {
        return $this->createQueryBuilder('p')
            ->select('COUNT(p.id)')
            ->where('p.user = :user')
            ->andWhere('p.evenement = :evenement')
            ->setParameter('user', $user)
            ->setParameter('evenement', $evenement)
            ->getQuery()
            ->getSingleScalarResult() > 0;
    }

    /**
     * @return int Nombre de participants pour un événement
     */
    public function countByEvenement(Evenement $evenement): int
    {
        return (int) $this->createQueryBuilder('p')
            ->select('COUNT(p.id)')
            ->where('p.evenement = :evenement')
            ->setParameter('evenement', $evenement)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * @return ParticipationEvenement[]
     */
    public function findByUser(User $user): array
    {
        return $this->createQueryBuilder('p')
            ->innerJoin('p.evenement', 'e')
            ->addSelect('e')
            ->where('p.user = :user')
            ->setParameter('user', $user)
            ->orderBy('p.dateParticipation', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
