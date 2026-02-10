<?php

namespace App\Repository;

use App\Entity\CommandeItem; // On utilise l'entité CommandeItem
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<CommandeItem>
 * 
 * Cette classe permet de gérer l'accès aux données de l'entité CommandeItem
 * Elle hérite de ServiceEntityRepository qui fournit déjà des méthodes
 * comme find(), findOneBy(), findAll(), findBy()
 */
class CommandeItemRepository extends ServiceEntityRepository
{
    // ===== CONSTRUCTEUR =====
    public function __construct(ManagerRegistry $registry)
    {
        // Appelle le constructeur parent en lui indiquant l'entité gérée
        parent::__construct($registry, CommandeItem::class);
    }


}
