<?php

namespace App\Entity;

use App\Repository\CandidatureRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: CandidatureRepository::class)]
class Candidature
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: OffreStage::class, inversedBy: 'candidatures')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?OffreStage $offreStage = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?User $apprenant = null;

    #[ORM\Column(length: 50)]
    private ?string $statut = 'en_attente'; // en_attente, acceptee, refusee

    #[ORM\Column(type: Types::TEXT)]
    #[Assert\NotBlank(message: 'La lettre de motivation est obligatoire')]
    private ?string $lettreMotivation = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $cv = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $dateCandidature = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $commentaire = null; // Commentaire de la société

    public function __construct()
    {
        $this->dateCandidature = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getOffreStage(): ?OffreStage
    {
        return $this->offreStage;
    }

    public function setOffreStage(?OffreStage $offreStage): static
    {
        $this->offreStage = $offreStage;
        return $this;
    }

    public function getApprenant(): ?User
    {
        return $this->apprenant;
    }

    public function setApprenant(?User $apprenant): static
    {
        $this->apprenant = $apprenant;
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

    public function getLettreMotivation(): ?string
    {
        return $this->lettreMotivation;
    }

    public function setLettreMotivation(string $lettreMotivation): static
    {
        $this->lettreMotivation = $lettreMotivation;
        return $this;
    }

    public function getCv(): ?string
    {
        return $this->cv;
    }

    public function setCv(?string $cv): static
    {
        $this->cv = $cv;
        return $this;
    }

    public function getDateCandidature(): ?\DateTimeInterface
    {
        return $this->dateCandidature;
    }

    public function setDateCandidature(\DateTimeInterface $dateCandidature): static
    {
        $this->dateCandidature = $dateCandidature;
        return $this;
    }

    public function getCommentaire(): ?string
    {
        return $this->commentaire;
    }

    public function setCommentaire(?string $commentaire): static
    {
        $this->commentaire = $commentaire;
        return $this;
    }
}
