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

    public function searchBySocietePaginated(User $societe, array $filters, int $page, int $limit): array
    {
        $page = max(1, $page);
        $limit = max(1, min(100, $limit));

        $qb = $this->createQueryBuilder('o')
            ->where('o.societe = :societe')
            ->setParameter('societe', $societe);

        $titre = isset($filters['titre']) ? trim((string) $filters['titre']) : '';
        if ($titre !== '') {
            $qb->andWhere('LOWER(o.titre) LIKE :titre')
                ->setParameter('titre', '%' . mb_strtolower($titre) . '%');
        }

        $typeStage = isset($filters['typeStage']) ? trim((string) $filters['typeStage']) : '';
        if ($typeStage !== '') {
            $qb->andWhere('o.typeStage = :typeStage')
                ->setParameter('typeStage', $typeStage);
        }

        $statut = isset($filters['statut']) ? trim((string) $filters['statut']) : '';
        if ($statut !== '') {
            $qb->andWhere('o.statut = :statut')
                ->setParameter('statut', $statut);
        }

        $domaine = isset($filters['domaine']) ? trim((string) $filters['domaine']) : '';
        if ($domaine !== '') {
            $qb->andWhere('LOWER(o.domaine) LIKE :domaine')
                ->setParameter('domaine', '%' . mb_strtolower($domaine) . '%');
        }

        $lieu = isset($filters['lieu']) ? trim((string) $filters['lieu']) : '';
        if ($lieu !== '') {
            $qb->andWhere('LOWER(o.lieu) LIKE :lieu')
                ->setParameter('lieu', '%' . mb_strtolower($lieu) . '%');
        }

        $qb->orderBy('o.datePublication', 'DESC');

        $countQb = clone $qb;
        $total = (int) $countQb
            ->select('COUNT(o.id)')
            ->getQuery()
            ->getSingleScalarResult();

        $items = $qb
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        return [
            'items' => $items,
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
        ];
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
