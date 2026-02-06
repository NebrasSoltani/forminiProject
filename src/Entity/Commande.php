<?php

namespace App\Entity;

use App\Repository\CommandeRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CommandeRepository::class)]
class Commande
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?User $utilisateur = null;

    #[ORM\Column(length: 100, unique: true)]
    private ?string $reference = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $dateCommande = null;

    #[ORM\Column(length: 50)]
    private ?string $statut = 'en_attente'; // en_attente, confirmee, expediee, livree, annulee

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $total = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $adresseLivraison = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $telephone = null;

    #[ORM\OneToMany(mappedBy: 'commande', targetEntity: CommandeItem::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $items;

    public function __construct()
    {
        $this->dateCommande = new \DateTime();
        $this->reference = 'CMD-' . strtoupper(uniqid());
        $this->items = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUtilisateur(): ?User
    {
        return $this->utilisateur;
    }

    public function setUtilisateur(?User $utilisateur): static
    {
        $this->utilisateur = $utilisateur;
        return $this;
    }

    public function getReference(): ?string
    {
        return $this->reference;
    }

    public function setReference(string $reference): static
    {
        $this->reference = $reference;
        return $this;
    }

    public function getDateCommande(): ?\DateTimeInterface
    {
        return $this->dateCommande;
    }

    public function setDateCommande(\DateTimeInterface $dateCommande): static
    {
        $this->dateCommande = $dateCommande;
        return $this;
    }

    public function getStatut(): ?string
    {
        return $this->statut;
    }

    public function setStatut(string $statut): static
    {
        $this->statut = $statut;
        return $this;
    }

    public function getTotal(): ?string
    {
        return $this->total;
    }

    public function setTotal(string $total): static
    {
        $this->total = $total;
        return $this;
    }

    public function getAdresseLivraison(): ?string
    {
        return $this->adresseLivraison;
    }

    public function setAdresseLivraison(?string $adresseLivraison): static
    {
        $this->adresseLivraison = $adresseLivraison;
        return $this;
    }

    public function getTelephone(): ?string
    {
        return $this->telephone;
    }

    public function setTelephone(?string $telephone): static
    {
        $this->telephone = $telephone;
        return $this;
    }

    /**
     * @return Collection<int, CommandeItem>
     */
    public function getItems(): Collection
    {
        return $this->items;
    }

    public function addItem(CommandeItem $item): static
    {
        if (!$this->items->contains($item)) {
            $this->items->add($item);
            $item->setCommande($this);
        }
        return $this;
    }

    public function removeItem(CommandeItem $item): static
    {
        if ($this->items->removeElement($item)) {
            if ($item->getCommande() === $this) {
                $item->setCommande(null);
            }
        }
        return $this;
    }

    public function calculerTotal(): float
    {
        $total = 0;
        foreach ($this->items as $item) {
            $total += (float)$item->getPrixUnitaire() * $item->getQuantite();
        }
        $this->total = (string)$total;
        return $total;
    }
}
