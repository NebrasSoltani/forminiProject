<?php

namespace App\Repository;

use App\Entity\Commande;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Commande>
 */
class CommandeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Commande::class);
    }

    /**
     * Récupère toutes les commandes d'un utilisateur, triées par date décroissante
     *
     * @param User $user
     * @return Commande[]
     */
    public function findByUser(User $user): array
    {
        return $this->createQueryBuilder('c')
            ->where('c.utilisateur = :user')
            ->setParameter('user', $user)
            ->orderBy('c.dateCommande', 'DESC')
            ->getQuery()
            ->getResult();
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
            ->getOneOrNullResult();
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
            ->where('c.dateCommande BETWEEN :start AND :end')
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
            ->setMaxResults($limit)
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
            ->select('COUNT(c.id)')
            ->getQuery()
            ->getSingleScalarResult();
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
