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

    private ?string $titre = null;



    #[ORM\Column(type: Types::TEXT)]

    #[Assert\NotBlank(message: 'La description est obligatoire')]

    private ?string $description = null;



    #[ORM\Column(length: 255)]

    #[Assert\NotBlank(message: 'Le nom de l\'entreprise est obligatoire')]

    private ?string $entreprise = null;



    #[ORM\Column(length: 50, nullable: true)]

    private ?string $domaine = null;



    #[ORM\Column(type: Types::TEXT, nullable: true)]

    private ?string $competencesRequises = null;



    #[ORM\Column(length: 100, nullable: true)]

    private ?string $profilDemande = null;



    #[ORM\Column(length: 100)]

    #[Assert\NotBlank]

    private ?string $duree = null; // Ex: "3 mois", "6 mois"



    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]

    private ?\DateTimeInterface $dateDebut = null;



    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]

    private ?\DateTimeInterface $dateFin = null;



    #[ORM\Column(length: 100)]

    #[Assert\Choice(choices: ['stage_observation', 'stage_application', 'stage_perfectionnement', 'pfe'])]

    private ?string $typeStage = null;



    #[ORM\Column(length: 255)]

    #[Assert\NotBlank]

    private ?string $lieu = null;



    #[ORM\Column(length: 100, nullable: true)]

    private ?string $remuneration = null;



    #[ORM\Column(length: 100, nullable: true)]

    #[Assert\Email(message: 'Email invalide')]

    private ?string $contactEmail = null;



    #[ORM\Column(length: 20, nullable: true)]

    private ?string $contactTel = null;



    #[ORM\Column(length: 50)]

    private ?string $statut = 'publiee'; // publiee, expiree, fermee



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



    public function getCompetencesRequises(): ?string

    {

        return $this->competencesRequises;

    }



    public function setCompetencesRequises(?string $competencesRequises): static

    {

        $this->competencesRequises = $competencesRequises;

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



    public function getDomaine(): ?string

    {

        return $this->domaine;

    }



    public function setDomaine(?string $domaine): static

    {

        $this->domaine = $domaine;

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



    public function getRemuneration(): ?string

    {

        return $this->remuneration;

    }



    public function setRemuneration(?string $remuneration): static

    {

        $this->remuneration = $remuneration;

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



    public function setDatePublication(\DateTimeInterface $datePublication): static

    {

        $this->datePublication = $datePublication;

        return $this;

    }



    /**

     * @return Collection<int, Candidature>

     */

    public function getCandidatures(): Collection

    {

        return $this->candidatures;

    }



    public function addCandidature(Candidature $candidature): static

    {

        if (!$this->candidatures->contains($candidature)) {

            $this->candidatures->add($candidature);

            $candidature->setOffreStage($this);

        }

        return $this;

    }



    public function removeCandidature(Candidature $candidature): static

    {

        if ($this->candidatures->removeElement($candidature)) {

            if ($candidature->getOffreStage() === $this) {

                $candidature->setOffreStage(null);

            }

        }

        return $this;

    }

}

