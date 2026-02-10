<?php

namespace App\Entity;

use App\Repository\CommandeRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

// Déclare que cette classe est une entité Doctrine et qu'elle utilise le repository CommandeRepository
#[ORM\Entity(repositoryClass: CommandeRepository::class)]
class Commande
{
    // ===== IDENTIFIANT =====
    #[ORM\Id]
    #[ORM\GeneratedValue] // Auto-incrément
    #[ORM\Column]
    private ?int $id = null;

    // ===== UTILISATEUR =====
    // Relation ManyToOne avec l'entité User
    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')] 
    // Si l'utilisateur est supprimé, ses commandes sont aussi supprimées
    private ?User $utilisateur = null;

    // ===== RÉFÉRENCE =====
    #[ORM\Column(length: 100, unique: true)]
    private ?string $reference = null;

    // ===== DATE DE COMMANDE =====
    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $dateCommande = null;

    // ===== STATUT =====
    #[ORM\Column(length: 50)]
    private ?string $statut = 'en_attente'; 
    // Statuts possibles : en_attente, confirmee, expediee, livree, annulee

    // ===== TOTAL =====
    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $total = null; // total de la commande

    // ===== ADRESSE DE LIVRAISON =====
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $adresseLivraison = null;

    // ===== TÉLÉPHONE =====
    #[ORM\Column(length: 20, nullable: true)]
    private ?string $telephone = null;

    // ===== INFORMATIONS DE PAIEMENT STRIPE =====
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $stripeSessionId = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $stripePaymentIntentId = null;

    // ===== ITEMS DE LA COMMANDE =====
    #[ORM\OneToMany(mappedBy: 'commande', targetEntity: CommandeItem::class, 
                    cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $items; // Collection d'objets CommandeItem

    // ===== CONSTRUCTEUR =====
    public function __construct()
    {
        $this->dateCommande = new \DateTime(); // date actuelle par défaut
        $this->reference = 'CMD-' . strtoupper(uniqid()); // référence unique auto-générée
        $this->items = new ArrayCollection(); // initialise la collection d’items
    }

    // ===== GETTERS & SETTERS =====
    public function getId(): ?int { return $this->id; }

    public function getUtilisateur(): ?User { return $this->utilisateur; }
    public function setUtilisateur(?User $utilisateur): static { 
        $this->utilisateur = $utilisateur; return $this; 
    }

    public function getReference(): ?string { return $this->reference; }
    public function setReference(string $reference): static { 
        $this->reference = $reference; return $this; 
    }

    public function getDateCommande(): ?\DateTimeInterface { return $this->dateCommande; }
    public function setDateCommande(\DateTimeInterface $dateCommande): static { 
        $this->dateCommande = $dateCommande; return $this; 
    }

    public function getStatut(): ?string { return $this->statut; }
    public function setStatut(string $statut): static { 
        $this->statut = $statut; return $this; 
    }

    public function getTotal(): ?string { return $this->total; }
    public function setTotal(string $total): static { 
        $this->total = $total; return $this; 
    }

    public function getAdresseLivraison(): ?string { return $this->adresseLivraison; }
    public function setAdresseLivraison(?string $adresseLivraison): static { 
        $this->adresseLivraison = $adresseLivraison; return $this; 
    }

    public function getTelephone(): ?string { return $this->telephone; }
    public function setTelephone(?string $telephone): static { 
        $this->telephone = $telephone; return $this; 
    }

    public function getStripeSessionId(): ?string { return $this->stripeSessionId; }
    public function setStripeSessionId(?string $stripeSessionId): static { 
        $this->stripeSessionId = $stripeSessionId; return $this; 
    }

    public function getStripePaymentIntentId(): ?string { return $this->stripePaymentIntentId; }
    public function setStripePaymentIntentId(?string $stripePaymentIntentId): static { 
        $this->stripePaymentIntentId = $stripePaymentIntentId; return $this; 
    }

    // ===== GESTION DES ITEMS =====
    /**
     * @return Collection<int, CommandeItem>
     */
    public function getItems(): Collection { return $this->items; }

    public function addItem(CommandeItem $item): static
    {
        if (!$this->items->contains($item)) {
            $this->items->add($item);      // ajoute l’item à la commande
            $item->setCommande($this);     // définit la relation bidirectionnelle
        }
        return $this;
    }

    public function removeItem(CommandeItem $item): static
    {
        if ($this->items->removeElement($item)) {
            if ($item->getCommande() === $this) {
                $item->setCommande(null); // dissocie l’item
            }
        }
        return $this;
    }

    // ===== CALCULER LE TOTAL =====
    public function calculerTotal(): float
    {
        $total = 0;
        foreach ($this->items as $item) {
            $total += (float)$item->getPrixUnitaire() * $item->getQuantite();
        }
        $this->total = (string)$total; // enregistre le total dans l'entité
        return $total;
    }
}
