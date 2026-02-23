<?php

namespace App\Repository;

use App\Entity\LiveReaction;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class LiveReactionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, LiveReaction::class);
    }

    public function findRecentByEvent(int $eventId, int $limit = 10)
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.evenement = :eventId')
            ->setParameter('eventId', $eventId)
            ->orderBy('r.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function countByEvent(int $eventId)
    {
        return $this->createQueryBuilder('r')
            ->select('COUNT(r.id)')
            ->andWhere('r.evenement = :eventId')
            ->setParameter('eventId', $eventId)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function getStatsByEvent(): array
    {
        return $this->createQueryBuilder('r')
            ->select('e.titre as event_title, COUNT(r.id) as reaction_count')
            ->join('r.evenement', 'e')
            ->groupBy('e.id')
            ->orderBy('reaction_count', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function getReactionDistributionByEvent(int $eventId): array
    {
        return $this->createQueryBuilder('r')
            ->select('r.type, COUNT(r.id) as count')
            ->andWhere('r.evenement = :eventId')
            ->setParameter('eventId', $eventId)
            ->groupBy('r.type')
            ->getQuery()
            ->getResult();
    }

    public function getReactionDistribution(): array
    {
        return $this->createQueryBuilder('r')
            ->select('r.type, COUNT(r.id) as count')
            ->groupBy('r.type')
            ->getQuery()
            ->getResult();
    }

    public function getTopReactorsByEvent(int $eventId, int $limit = 3): array
    {
        return $this->createQueryBuilder('r')
            ->select('u.prenom, u.nom, COUNT(r.id) as count')
            ->join('r.user', 'u')
            ->andWhere('r.evenement = :eventId')
            ->setParameter('eventId', $eventId)
            ->groupBy('u.id')
            ->orderBy('count', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function getTotalReactions(): int
    {
        return $this->createQueryBuilder('r')
            ->select('COUNT(r.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function getActivityTimelineByEvent(int $eventId): array
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql = 'SELECT DATE_FORMAT(created_at, \'%H:%i\') as minute, COUNT(id) as count FROM live_reaction WHERE evenement_id = ? GROUP BY minute ORDER BY minute ASC';
        return $conn->executeQuery($sql, [$eventId])->fetchAllAssociative();
    }
}