<?php

namespace App\Entity;

use App\Repository\EvenementRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: EvenementRepository::class)]
class Evenement
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: "Le titre est requis")]
    #[Assert\Length(
        min: 5,
        max: 255,
        minMessage: "Le titre doit contenir au moins {{ limit }} caractères",
        maxMessage: "Le titre ne peut pas dépasser {{ limit }} caractères"
    )]
    private ?string $titre = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Assert\NotBlank(message: "La description est requise")]
    #[Assert\Length(
        min: 10,
        minMessage: "La description doit contenir au moins {{ limit }} caractères"
    )]
    private ?string $description = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Assert\NotBlank(message: "La date de début est requise")]
    private ?\DateTimeInterface $dateDebut = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Assert\NotBlank(message: "La date de fin est requise")]
    #[Assert\Expression(
        "this.getDateDebut() == null or this.getDateFin() == null or this.getDateDebut() < this.getDateFin()",
        message: "La date de fin doit être après la date de début"
    )]
    private ?\DateTimeInterface $dateFin = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: "Le lieu est requis")]
    #[Assert\Length(
        min: 3,
        max: 255,
        minMessage: "Le lieu doit contenir au moins {{ limit }} caractères",
        maxMessage: "Le lieu ne peut pas dépasser {{ limit }} caractères"
    )]
    private ?string $lieu = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $image = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull(message: "L'organisateur est requis")]
    private ?User $organisateur = null;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    #[Assert\Positive(message: "Le nombre de places doit être positif")]
    #[Assert\Range(
        min: 1,
        max: 10000,
        notInRangeMessage: "Le nombre de places doit être entre {{ min }} et {{ max }}"
    )]
    private ?int $nombrePlaces = null;

    #[ORM\Column]
    private ?bool $isActif = true;

    #[ORM\Column(length: 50)]
    #[Assert\NotBlank(message: "Le type est requis")]
    #[Assert\Choice(
        choices: ['Conférence', 'Atelier', 'Webinaire', 'Formation', 'Networking', 'Séminaire', 'Hackathon', 'Autre'],
        message: "Le type '{{ value }}' n'est pas valide"
    )]
    private ?string $type = null;

    #[ORM\OneToMany(mappedBy: 'evenement', targetEntity: ParticipationEvenement::class, cascade: ['remove'], orphanRemoval: true)]
    private Collection $participations;

    public function __construct()
    {
        $this->participations = new ArrayCollection();
    }

    /**
     * @return Collection<int, ParticipationEvenement>
     */
    public function getParticipations(): Collection
    {
        return $this->participations;
    }

    public function addParticipation(ParticipationEvenement $participation): static
    {
        if (!$this->participations->contains($participation)) {
            $this->participations->add($participation);
            $participation->setEvenement($this);
        }
        return $this;
    }

    public function removeParticipation(ParticipationEvenement $participation): static
    {
        if ($this->participations->removeElement($participation)) {
            if ($participation->getEvenement() === $this) {
                $participation->setEvenement(null);
            }
        }
        return $this;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitre(): ?string
    {
        return $this->titre;
    }

    // ✅ CHANGÉ : Accepte ?string au lieu de string
    public function setTitre(?string $titre): static
    {
        $this->titre = $titre;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    // ✅ CHANGÉ : Accepte ?string au lieu de string
    public function setDescription(?string $description): static
    {
        $this->description = $description;
        return $this;
    }

    public function getDateDebut(): ?\DateTimeInterface
    {
        return $this->dateDebut;
    }

    public function setDateDebut(?\DateTimeInterface $dateDebut): static
    {
        $this->dateDebut = $dateDebut;
        return $this;
    }

    public function getDateFin(): ?\DateTimeInterface
    {
        return $this->dateFin;
    }

    public function setDateFin(?\DateTimeInterface $dateFin): static
    {
        $this->dateFin = $dateFin;
        return $this;
    }

    public function getLieu(): ?string
    {
        return $this->lieu;
    }

    // ✅ CHANGÉ : Accepte ?string au lieu de string
    public function setLieu(?string $lieu): static
    {
        $this->lieu = $lieu;
        return $this;
    }

    public function getImage(): ?string
    {
        return $this->image;
    }

    public function setImage(?string $image): static
    {
        $this->image = $image;
        return $this;
    }

    public function getOrganisateur(): ?User
    {
        return $this->organisateur;
    }

    public function setOrganisateur(?User $organisateur): static
    {
        $this->organisateur = $organisateur;
        return $this;
    }

    public function getNombrePlaces(): ?int
    {
        return $this->nombrePlaces;
    }

    public function setNombrePlaces(?int $nombrePlaces): static
    {
        $this->nombrePlaces = $nombrePlaces;
        return $this;
    }

    public function isActif(): ?bool
    {
        return $this->isActif;
    }

    public function setActif(bool $isActif): static
    {
        $this->isActif = $isActif;
        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    // ✅ CHANGÉ : Accepte ?string au lieu de string
    public function setType(?string $type): static
    {
        $this->type = $type;
        return $this;
    }
}