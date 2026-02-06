<?php

namespace App\Repository;

use App\Entity\Paiement;
use App\Entity\Inscription;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Paiement>
 */
class PaiementRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Paiement::class);
    }

    public function findByInscription(Inscription $inscription): ?Paiement
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.inscription = :inscription')
            ->setParameter('inscription', $inscription)
            ->orderBy('p.dateCreation', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findByReference(string $reference): ?Paiement
    {
        return $this->findOneBy(['referenceTransaction' => $reference]);
    }
}
