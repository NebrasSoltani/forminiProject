<?php

namespace App\Repository;

use App\Entity\Blog;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Blog>
 */
class BlogRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Blog::class);
    }

    /**
     * Recherche et tri des blogs avec filtres
     */
    public function searchAndSort(array $filters = [], array $sorting = []): array
    {
        $qb = $this->createQueryBuilder('b')
            ->leftJoin('b.auteur', 'a')
            ->leftJoin('b.evenement', 'e')
            ->addSelect('a')
            ->addSelect('e');

        // Filtre par recherche texte
        if (!empty($filters['search'])) {
            $qb->andWhere('b.titre LIKE :search 
                OR b.contenu LIKE :search 
                OR b.resume LIKE :search 
                OR a.nom LIKE :search 
                OR a.prenom LIKE :search')
               ->setParameter('search', '%' . $filters['search'] . '%');
        }

        // Filtre par catégorie
        if (!empty($filters['categorie'])) {
            $qb->andWhere('b.categorie = :categorie')
               ->setParameter('categorie', $filters['categorie']);
        }

        // Filtre par statut (gestion des différents formats)
        if (isset($filters['isPublie']) && $filters['isPublie'] !== '' && $filters['isPublie'] !== null) {
            $value = $filters['isPublie'];
            // Convertir en booléen
            if ($value === 'true' || $value === '1' || $value === 1 || $value === true) {
                $value = true;
            } else {
                $value = false;
            }
            $qb->andWhere('b.isPublie = :isPublie')
               ->setParameter('isPublie', $value);
        }

        // Filtre par auteur
        if (!empty($filters['auteur'])) {
            $qb->andWhere('a.id = :auteurId')
               ->setParameter('auteurId', $filters['auteur']);
        }

        // Filtre par événement
        if (!empty($filters['evenement'])) {
            $qb->andWhere('e.id = :evenementId')
               ->setParameter('evenementId', $filters['evenement']);
        }

        // Filtre par date de publication (dateFrom)
        if (!empty($filters['dateFrom'])) {
            try {
                // Convertir la date en DateTime si c'est une string
                $dateFrom = is_string($filters['dateFrom']) 
                    ? new \DateTime($filters['dateFrom']) 
                    : $filters['dateFrom'];
                
                $qb->andWhere('b.datePublication >= :dateFrom')
                   ->setParameter('dateFrom', $dateFrom->setTime(0, 0, 0));
            } catch (\Exception $e) {
                // Ignorer les dates invalides
            }
        }

        // Filtre par date de publication (dateTo)
        if (!empty($filters['dateTo'])) {
            try {
                // Convertir la date en DateTime si c'est une string
                $dateTo = is_string($filters['dateTo']) 
                    ? new \DateTime($filters['dateTo']) 
                    : $filters['dateTo'];
                
                $qb->andWhere('b.datePublication <= :dateTo')
                   ->setParameter('dateTo', $dateTo->setTime(23, 59, 59));
            } catch (\Exception $e) {
                // Ignorer les dates invalides
            }
        }

        // Gestion du tri
        $sortField = $sorting['field'] ?? 'b.datePublication';
        $sortDirection = strtoupper($sorting['direction'] ?? 'DESC');
        
        // Validation de la direction
        if (!in_array($sortDirection, ['ASC', 'DESC'])) {
            $sortDirection = 'DESC';
        }

        $qb->orderBy($sortField, $sortDirection);

        return $qb->getQuery()->getResult();
    }

    /**
     * Récupère toutes les catégories distinctes
     */
    public function getAllCategories(): array
    {
        $result = $this->createQueryBuilder('b')
            ->select('DISTINCT b.categorie')
            ->where('b.categorie IS NOT NULL')
            ->orderBy('b.categorie', 'ASC')
            ->getQuery()
            ->getScalarResult();
        
        return array_column($result, 'categorie');
    }
}