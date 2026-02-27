<?php

namespace App\Entity;

use App\Repository\CommandeItemRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

// Déclare que cette classe est une entité Doctrine et utilise le repository CommandeItemRepository
#[ORM\Entity(repositoryClass: CommandeItemRepository::class)]
class CommandeItem
{
    // ===== IDENTIFIANT =====
    #[ORM\Id]
    #[ORM\GeneratedValue] // Auto-incrément
    #[ORM\Column]
    private ?int $id = null;

    // ===== RELATION AVEC LA COMMANDE =====
    // Chaque item appartient à une seule commande (ManyToOne)
    #[ORM\ManyToOne(targetEntity: Commande::class, inversedBy: 'items')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')] 
    // Si la commande est supprimée, ses items le sont aussi
    private ?Commande $commande = null;

    // ===== RELATION AVEC LE PRODUIT =====
    // Chaque item correspond à un produit spécifique
    #[ORM\ManyToOne(targetEntity: Produit::class, inversedBy: 'commandeItems')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Produit $produit = null;

    // ===== NOM DU PRODUIT =====
    // On stocke le nom du produit au moment de la commande (au cas où le produit change plus tard)
    #[ORM\Column(length: 255)]
    private ?string $nomProduit = null;

    // ===== QUANTITÉ =====
    #[ORM\Column]
    private ?int $quantite = null;

    // ===== PRIX UNITAIRE =====
    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $prixUnitaire = null;

    // ===== GETTERS & SETTERS =====
    public function getId(): ?int { return $this->id; }

    public function getCommande(): ?Commande { return $this->commande; }
    public function setCommande(?Commande $commande): static { 
        $this->commande = $commande; return $this; 
    }

    public function getProduit(): ?Produit { return $this->produit; }
    public function setProduit(?Produit $produit): static { 
        $this->produit = $produit; return $this; 
    }

    public function getQuantite(): ?int { return $this->quantite; }
    public function setQuantite(int $quantite): static { 
        $this->quantite = $quantite; return $this; 
    }

    public function getPrixUnitaire(): ?string { return $this->prixUnitaire; }
    public function setPrixUnitaire(string $prixUnitaire): static { 
        $this->prixUnitaire = $prixUnitaire; return $this; 
    }

    public function getNomProduit(): ?string { return $this->nomProduit; }
    public function setNomProduit(string $nomProduit): static { 
        $this->nomProduit = $nomProduit; return $this; 
    }

    // ===== CALCUL DU SOUS-TOTAL =====
    // Multiplie la quantité par le prix unitaire pour cet item
    public function getSousTotal(): float
    {
        return (float)$this->prixUnitaire * $this->quantite;
    }
}
