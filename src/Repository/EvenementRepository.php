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

    // ========================================
    // NOUVELLES MÉTHODES AJOUTÉES CI-DESSOUS
    // ========================================

    /**
     * Recherche et tri d'événements avec filtres
     */
    public function findBySearchAndFilters(
        ?string $searchTerm = null,
        ?string $type = null,
        ?string $statut = null,
        ?string $sortBy = 'dateDebut',
        ?string $sortOrder = 'DESC'
    ) {
        $qb = $this->createQueryBuilder('e')
            ->leftJoin('e.organisateur', 'o')
            ->addSelect('o');

        // Recherche par titre, description ou lieu - CORRECTION ICI
        if ($searchTerm && trim($searchTerm) !== '') {
            $qb->andWhere(
                $qb->expr()->orX(
                    $qb->expr()->like('e.titre', ':search'),
                    $qb->expr()->like('e.description', ':search'),
                    $qb->expr()->like('e.lieu', ':search')
                )
            )
            ->setParameter('search', '%' . trim($searchTerm) . '%');
        }

        // Filtre par type
        if ($type && trim($type) !== '') {
            $qb->andWhere('e.type = :type')
               ->setParameter('type', $type);
        }

        // Filtre par statut (actif/inactif)
        if ($statut !== null && trim($statut) !== '') {
            $qb->andWhere('e.isActif = :statut')
               ->setParameter('statut', $statut === 'actif');
        }

        // Tri
        $allowedSortFields = ['titre', 'dateDebut', 'dateFin', 'type', 'nombrePlaces'];
        if (in_array($sortBy, $allowedSortFields)) {
            $qb->orderBy('e.' . $sortBy, $sortOrder === 'ASC' ? 'ASC' : 'DESC');
        } else {
            $qb->orderBy('e.dateDebut', 'DESC');
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Statistiques globales
     */
    public function getStatistiques(): array
    {
        $now = new \DateTime();
        
        // Total événements
        $totalEvenements = $this->count([]);
        
        // Événements actifs
        $evenementsActifs = $this->count(['isActif' => true]);
        
        // Événements à venir
        $qb = $this->createQueryBuilder('e');
        $evenementsAvenir = $qb->select('COUNT(e.id)')
            ->where('e.dateDebut > :now')
            ->setParameter('now', $now)
            ->getQuery()
            ->getSingleScalarResult();
        
        // Événements passés
        $qb2 = $this->createQueryBuilder('e');
        $evenementsPasses = $qb2->select('COUNT(e.id)')
            ->where('e.dateFin < :now')
            ->setParameter('now', $now)
            ->getQuery()
            ->getSingleScalarResult();
        
        // Événements en cours
        $qb3 = $this->createQueryBuilder('e');
        $evenementsEnCours = $qb3->select('COUNT(e.id)')
            ->where('e.dateDebut <= :now')
            ->andWhere('e.dateFin >= :now')
            ->setParameter('now', $now)
            ->getQuery()
            ->getSingleScalarResult();
        
        // Répartition par type
        $qb4 = $this->createQueryBuilder('e');
        $repartitionParType = $qb4->select('e.type, COUNT(e.id) as nombre')
            ->groupBy('e.type')
            ->getQuery()
            ->getResult();
        
        // Moyenne de places par événement
        $qb5 = $this->createQueryBuilder('e');
        $moyennePlaces = $qb5->select('AVG(e.nombrePlaces)')
            ->where('e.nombrePlaces IS NOT NULL')
            ->getQuery()
            ->getSingleScalarResult();

        return [
            'total' => $totalEvenements,
            'actifs' => $evenementsActifs,
            'a_venir' => $evenementsAvenir,
            'passes' => $evenementsPasses,
            'en_cours' => $evenementsEnCours,
            'par_type' => $repartitionParType,
            'moyenne_places' => round($moyennePlaces ?? 0, 2),
        ];
    }

    /**
     * Événements populaires (avec le plus de places)
     */
    public function findTopByPlaces(int $limit = 5): array
    {
        return $this->createQueryBuilder('e')
            ->where('e.nombrePlaces IS NOT NULL')
            ->orderBy('e.nombrePlaces', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}