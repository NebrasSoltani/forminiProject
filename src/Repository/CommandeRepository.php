<?php

namespace App\Repository;

use App\Entity\Commande;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Commande>
 * 
 * Repository dédié à l'entité Commande.
 * Permet de créer des requêtes personnalisées pour récupérer, filtrer ou compter des commandes.
 */
class CommandeRepository extends ServiceEntityRepository
{
    // ===== CONSTRUCTEUR =====
    public function __construct(ManagerRegistry $registry)
    {
        // On indique au parent que ce repository gère l'entité Commande
        parent::__construct($registry, Commande::class);
    }

    // ===============================================
    // Méthodes personnalisées pour interroger les commandes
    // ===============================================

    /**
     * Récupère toutes les commandes d'un utilisateur, triées par date décroissante
     *
     * @param User $user
     * @return Commande[]
     */
    public function findByUser(User $user): array
    {
        return $this->createQueryBuilder('c') // "c" = alias pour Commande
            ->where('c.utilisateur = :user') // filtre par utilisateur
            ->setParameter('user', $user) // injecte l'objet User
            ->orderBy('c.dateCommande', 'DESC') // tri du plus récent au plus ancien
            ->getQuery()
            ->getResult(); // renvoie un tableau de Commande
    }

    /**
     * Récupère une commande unique par sa référence
     *
     * @param string $reference
     * @return Commande|null
     */
    public function findByReference(string $reference): ?Commande
    {
        return $this->createQueryBuilder('c')
            ->where('c.reference = :reference')
            ->setParameter('reference', $reference)
            ->getQuery()
            ->getOneOrNullResult(); // retourne soit une Commande, soit null
    }

    /**
     * Récupère toutes les commandes payées
     *
     * @return Commande[]
     */
    public function findPaidOrders(): array
    {
        return $this->createQueryBuilder('c')
            ->where('c.statut = :statut')
            ->setParameter('statut', 'paye')
            ->orderBy('c.dateCommande', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère les commandes d'un utilisateur filtrées par statut
     *
     * @param User $user
     * @param string $statut
     * @return Commande[]
     */
    public function findByUserAndStatus(User $user, string $statut): array
    {
        return $this->createQueryBuilder('c')
            ->where('c.utilisateur = :user')
            ->andWhere('c.statut = :statut')
            ->setParameters([
                'user' => $user,
                'statut' => $statut,
            ])
            ->orderBy('c.dateCommande', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère les commandes entre deux dates
     *
     * @param \DateTimeInterface $start
     * @param \DateTimeInterface $end
     * @return Commande[]
     */
    public function findBetweenDates(\DateTimeInterface $start, \DateTimeInterface $end): array
    {
        return $this->createQueryBuilder('c')
            ->where('c.dateCommande BETWEEN :start AND :end') // filtre sur une période
            ->setParameters([
                'start' => $start,
                'end' => $end,
            ])
            ->orderBy('c.dateCommande', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère les dernières commandes
     *
     * @param int $limit
     * @return Commande[]
     */
    public function findLatest(int $limit = 10): array
    {
        return $this->createQueryBuilder('c')
            ->orderBy('c.dateCommande', 'DESC')
            ->setMaxResults($limit) // limite le nombre de résultats
            ->getQuery()
            ->getResult();
    }

    /**
     * Compte le nombre total de commandes
     *
     * @return int
     */
    public function countAll(): int
    {
        return (int) $this->createQueryBuilder('c')
            ->select('COUNT(c.id)') // SELECT COUNT(*)
            ->getQuery()
            ->getSingleScalarResult(); // retourne un entier
    }

    /**
     * Compte le nombre de commandes par statut
     *
     * @param string $statut
     * @return int
     */
    public function countByStatus(string $statut): int
    {
        return (int) $this->createQueryBuilder('c')
            ->select('COUNT(c.id)')
            ->where('c.statut = :statut')
            ->setParameter('statut', $statut)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
