<?php

namespace App\Repository;

use App\Entity\Formation;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Formation>
 */
class FormationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Formation::class);
    }

    /**
     * Trouver toutes les formations publiées
     */
    public function findPublished(): array
    {
        return $this->createQueryBuilder('f')
            ->andWhere('f.statut = :statut')
            ->setParameter('statut', 'publiee')
            ->orderBy('f.datePublication', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouver les formations d'un formateur
     */
    public function findByFormateur(User $formateur): array
    {
        return $this->createQueryBuilder('f')
            ->andWhere('f.formateur = :formateur')
            ->setParameter('formateur', $formateur)
            ->orderBy('f.dateCreation', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Rechercher des formations
     */
    public function search(string $query, ?string $categorie = null, ?string $niveau = null): array
    {
        $qb = $this->createQueryBuilder('f')
            ->andWhere('f.statut = :statut')
            ->setParameter('statut', 'publiee');

        if ($query) {
            $qb->andWhere('f.titre LIKE :query OR f.descriptionCourte LIKE :query')
                ->setParameter('query', '%'.$query.'%');
        }

        if ($categorie) {
            $qb->andWhere('f.categorie = :categorie')
                ->setParameter('categorie', $categorie);
        }

        if ($niveau) {
            $qb->andWhere('f.niveau = :niveau')
                ->setParameter('niveau', $niveau);
        }

        return $qb->orderBy('f.datePublication', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouver les formations populaires
     */
    public function findPopular(int $limit = 6): array
    {
        return $this->createQueryBuilder('f')
            ->andWhere('f.statut = :statut')
            ->setParameter('statut', 'publiee')
            ->orderBy('f.datePublication', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}
