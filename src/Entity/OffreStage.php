<?php

namespace App\Entity;

use App\Repository\OffreStageRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: OffreStageRepository::class)]
class OffreStage
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?User $societe = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Le titre est obligatoire')]
    #[Assert\Length(
        min: 5,
        minMessage: 'Le titre doit contenir au moins {{ limit }} caractères'
    )]
    private ?string $titre = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Assert\NotBlank(message: 'La description est obligatoire')]
    #[Assert\Length(
        min: 30,
        minMessage: 'La description doit contenir au moins {{ limit }} caractères'
    )]
    private ?string $description = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: "Le nom de l'entreprise est obligatoire")]
    #[Assert\Length(
        min: 2,
        minMessage: "Le nom de l'entreprise est trop court"
    )]
    private ?string $entreprise = null;

    #[ORM\Column(length: 50, nullable: true)]
    #[Assert\Length(
        min: 3,
        minMessage: 'Le domaine doit contenir au moins {{ limit }} caractères'
    )]
    private ?string $domaine = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Assert\Length(
        min: 5,
        minMessage: 'Les compétences doivent être plus détaillées'
    )]
    private ?string $competencesRequises = null;

    #[ORM\Column(length: 100, nullable: true)]
    #[Assert\Length(
        min: 5,
        minMessage: 'Le profil demandé est trop court'
    )]
    private ?string $profilDemande = null;

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank(message: 'La durée est obligatoire')]
    #[Assert\Regex(
        pattern: '/^\d+\s(mois|semaines)$/',
        message: 'Format invalide (ex: 3 mois, 6 semaines)'
    )]
    private ?string $duree = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    #[Assert\Type(type: "\DateTimeInterface", message: 'Date de début invalide')]
    private ?\DateTimeInterface $dateDebut = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    #[Assert\Type(type: "\DateTimeInterface", message: 'Date de fin invalide')]
    #[Assert\GreaterThan(
        propertyPath: 'dateDebut',
        message: 'La date de fin doit être postérieure à la date de début'
    )]
    private ?\DateTimeInterface $dateFin = null;

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank(message: 'Le type de stage est obligatoire')]
    #[Assert\Choice(
        choices: ['stage_observation', 'stage_application', 'stage_perfectionnement', 'pfe'],
        message: 'Type de stage invalide'
    )]
    private ?string $typeStage = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Le lieu est obligatoire')]
    #[Assert\Length(
        min: 3,
        minMessage: 'Le lieu doit contenir au moins {{ limit }} caractères'
    )]
    private ?string $lieu = null;

    #[ORM\Column(length: 100, nullable: true)]
    #[Assert\Length(
        min: 2,
        minMessage: 'Valeur de rémunération invalide'
    )]
    private ?string $remuneration = null;

    #[ORM\Column(length: 100, nullable: true)]
    #[Assert\Email(message: 'Email invalide')]
    private ?string $contactEmail = null;

    #[ORM\Column(length: 20, nullable: true)]
    #[Assert\Regex(
        pattern: '/^[0-9]{8}$/',
        message: 'Numéro de téléphone invalide (8 chiffres)'
    )]
    private ?string $contactTel = null;

    #[ORM\Column(length: 50)]
    #[Assert\Choice(
        choices: ['publiee', 'expiree', 'fermee'],
        message: 'Statut invalide'
    )]
    private ?string $statut = 'publiee';

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $datePublication = null;

    #[ORM\OneToMany(mappedBy: 'offreStage', targetEntity: Candidature::class, cascade: ['remove'])]
    private Collection $candidatures;

    public function __construct()
    {
        $this->datePublication = new \DateTime();
        $this->candidatures = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSociete(): ?User
    {
        return $this->societe;
    }

    public function setSociete(?User $societe): static
    {
        $this->societe = $societe;
        return $this;
    }

    public function getTitre(): ?string
    {
        return $this->titre;
    }

    public function setTitre(string $titre): static
    {
        $this->titre = $titre;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): static
    {
        $this->description = $description;
        return $this;
    }

    public function getEntreprise(): ?string
    {
        return $this->entreprise;
    }

    public function setEntreprise(string $entreprise): static
    {
        $this->entreprise = $entreprise;
        return $this;
    }

    public function getDomaine(): ?string
    {
        return $this->domaine;
    }

    public function setDomaine(?string $domaine): static
    {
        $this->domaine = $domaine;
        return $this;
    }

    public function getCompetencesRequises(): ?string
    {
        return $this->competencesRequises;
    }

    public function setCompetencesRequises(?string $competencesRequises): static
    {
        $this->competencesRequises = $competencesRequises;
        return $this;
    }

    public function getProfilDemande(): ?string
    {
        return $this->profilDemande;
    }

    public function setProfilDemande(?string $profilDemande): static
    {
        $this->profilDemande = $profilDemande;
        return $this;
    }

    public function getDuree(): ?string
    {
        return $this->duree;
    }

    public function setDuree(string $duree): static
    {
        $this->duree = $duree;
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

    public function getTypeStage(): ?string
    {
        return $this->typeStage;
    }

    public function setTypeStage(string $typeStage): static
    {
        $this->typeStage = $typeStage;
        return $this;
    }

    public function getLieu(): ?string
    {
        return $this->lieu;
    }

    public function setLieu(string $lieu): static
    {
        $this->lieu = $lieu;
        return $this;
    }

    public function getRemuneration(): ?string
    {
        return $this->remuneration;
    }

    public function setRemuneration(?string $remuneration): static
    {
        $this->remuneration = $remuneration;
        return $this;
    }

    public function getContactEmail(): ?string
    {
        return $this->contactEmail;
    }

    public function setContactEmail(?string $contactEmail): static
    {
        $this->contactEmail = $contactEmail;
        return $this;
    }

    public function getContactTel(): ?string
    {
        return $this->contactTel;
    }

    public function setContactTel(?string $contactTel): static
    {
        $this->contactTel = $contactTel;
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

    public function getDatePublication(): ?\DateTimeInterface
    {
        return $this->datePublication;
    }

    public function getCandidatures(): Collection
    {
        return $this->candidatures;
    }
}
