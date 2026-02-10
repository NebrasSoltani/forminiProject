<?php

namespace App\Repository;

use App\Entity\Produit;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Produit>
 * 
 * Repository dédié à l'entité Produit.
 * Permet de gérer les requêtes personnalisées pour récupérer des produits.
 */
class ProduitRepository extends ServiceEntityRepository
{
    // ===== CONSTRUCTEUR =====
    public function __construct(ManagerRegistry $registry)
    {
        // On indique que ce repository gère l'entité Produit
        parent::__construct($registry, Produit::class);
    }

    // ===============================================
    // Méthodes personnalisées
    // ===============================================

    /**
     * Récupère tous les produits disponibles (statut = 'actif'), triés par date de création décroissante
     *
     * @return Produit[]
     */
    public function findDisponibles(): array
    {
        return $this->createQueryBuilder('p') // 'p' = alias pour Produit
            ->where('p.statut = :statut') // filtre par statut
            ->setParameter('statut', 'actif') // statut = actif
            ->orderBy('p.dateCreation', 'DESC') // tri du plus récent au plus ancien
            ->getQuery()
            ->getResult(); // renvoie un tableau de produits
    }

    /**
     * Récupère tous les produits d'une catégorie donnée, uniquement ceux disponibles (actif)
     *
     * @param string $categorie
     * @return Produit[]
     */
    public function findByCategorie(string $categorie): array
    {
        return $this->createQueryBuilder('p')
            ->where('p.categorie = :categorie') // filtre par catégorie
            ->andWhere('p.statut = :statut') // filtre par statut actif
            ->setParameter('categorie', $categorie)
            ->setParameter('statut', 'actif')
            ->orderBy('p.dateCreation', 'DESC') // tri du plus récent au plus ancien
            ->getQuery()
            ->getResult();
    }
}
