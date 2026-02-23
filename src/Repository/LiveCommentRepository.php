<?php

namespace App\Repository;

use App\Entity\LiveComment;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class LiveCommentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, LiveComment::class);
    }

    public function findRecentByEvent(int $eventId, int $limit = 20)
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.evenement = :eventId')
            ->setParameter('eventId', $eventId)
            ->orderBy('c.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function getStatsByEvent(): array
    {
        return $this->createQueryBuilder('c')
            ->select('e.titre as event_title, COUNT(c.id) as comment_count')
            ->join('c.evenement', 'e')
            ->groupBy('e.id')
            ->orderBy('comment_count', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function countByEvent(int $eventId): int
    {
        return $this->createQueryBuilder('c')
            ->select('COUNT(c.id)')
            ->andWhere('c.evenement = :eventId')
            ->setParameter('eventId', $eventId)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function getTotalComments(): int
    {
        return $this->createQueryBuilder('c')
            ->select('COUNT(c.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function getActivityTimelineByEvent(int $eventId): array
    {
        $conn = $this->getEntityManager()->getConnection();
        $format = '%H:%i';
        $sql = "SELECT DATE_FORMAT(created_at, '" . $format . "') as minute, COUNT(id) as count FROM live_comment WHERE evenement_id = ? GROUP BY minute ORDER BY minute ASC";
        return $conn->executeQuery($sql, [$eventId])->fetchAllAssociative();
    }
}