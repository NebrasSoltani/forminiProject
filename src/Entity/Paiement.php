<?php

namespace App\Entity;

use App\Repository\PaiementRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PaiementRepository::class)]
class Paiement
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Inscription::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Inscription $inscription = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $montant = null;

    #[ORM\Column(length: 50)]
    private ?string $methodePaiement = null; // carte, especes, virement, mobile_money

    #[ORM\Column(length: 50)]
    private ?string $statut = 'en_attente'; // en_attente, valide, refuse, annule

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $dateCreation = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dateValidation = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $referenceTransaction = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $detailsPaiement = null; // JSON pour stocker des infos supplémentaires

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $numeroTelephone = null; // Pour mobile money

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $nomTitulaire = null;

    public function __construct()
    {
        $this->dateCreation = new \DateTime();
        $this->referenceTransaction = $this->generateReference();
    }

    private function generateReference(): string
    {
        return 'PAY-' . strtoupper(uniqid());
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getInscription(): ?Inscription
    {
        return $this->inscription;
    }

    public function setInscription(?Inscription $inscription): static
    {
        $this->inscription = $inscription;
        return $this;
    }

    public function getMontant(): ?string
    {
        return $this->montant;
    }

    public function setMontant(string $montant): static
    {
        $this->montant = $montant;
        return $this;
    }

    public function getMethodePaiement(): ?string
    {
        return $this->methodePaiement;
    }

    public function setMethodePaiement(string $methodePaiement): static
    {
        $this->methodePaiement = $methodePaiement;
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

    public function getDateCreation(): ?\DateTimeInterface
    {
        return $this->dateCreation;
    }

    public function setDateCreation(\DateTimeInterface $dateCreation): static
    {
        $this->dateCreation = $dateCreation;
        return $this;
    }

    public function getDateValidation(): ?\DateTimeInterface
    {
        return $this->dateValidation;
    }

    public function setDateValidation(?\DateTimeInterface $dateValidation): static
    {
        $this->dateValidation = $dateValidation;
        return $this;
    }

    public function getReferenceTransaction(): ?string
    {
        return $this->referenceTransaction;
    }

    public function setReferenceTransaction(?string $referenceTransaction): static
    {
        $this->referenceTransaction = $referenceTransaction;
        return $this;
    }

    public function getDetailsPaiement(): ?string
    {
        return $this->detailsPaiement;
    }

    public function setDetailsPaiement(?string $detailsPaiement): static
    {
        $this->detailsPaiement = $detailsPaiement;
        return $this;
    }

    public function getNumeroTelephone(): ?string
    {
        return $this->numeroTelephone;
    }

    public function setNumeroTelephone(?string $numeroTelephone): static
    {
        $this->numeroTelephone = $numeroTelephone;
        return $this;
    }

    public function getNomTitulaire(): ?string
    {
        return $this->nomTitulaire;
    }

    public function setNomTitulaire(?string $nomTitulaire): static
    {
        $this->nomTitulaire = $nomTitulaire;
        return $this;
    }
}
