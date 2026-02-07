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
     * Retourne les blogs publiés triés par date
     */
    public function findPublishedBlogs()
    {
        return $this->createQueryBuilder('b')
            ->where('b.isPublie = :published')
            ->setParameter('published', true)
            ->orderBy('b.datePublication', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Retourne les blogs par catégorie
     */
    public function findByCategorie(string $categorie)
    {
        return $this->createQueryBuilder('b')
            ->where('b.categorie = :categorie')
            ->andWhere('b.isPublie = :published')
            ->setParameter('categorie', $categorie)
            ->setParameter('published', true)
            ->orderBy('b.datePublication', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Retourne les derniers blogs publiés
     */
    public function findLatestPublished(int $limit = 5)
    {
        return $this->createQueryBuilder('b')
            ->where('b.isPublie = :published')
            ->setParameter('published', true)
            ->orderBy('b.datePublication', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}
