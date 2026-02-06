<?php

namespace App\Entity;

use App\Repository\FormationRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: FormationRepository::class)]
class Formation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    // 1) Infos générales
    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Le titre est obligatoire')]
    private ?string $titre = null;

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank]
    private ?string $categorie = null;

    #[ORM\Column(length: 50)]
    #[Assert\Choice(choices: ['debutant', 'intermediaire', 'avance'])]
    private ?string $niveau = null;

    #[ORM\Column(length: 50)]
    #[Assert\NotBlank]
    private ?string $langue = null;

    #[ORM\Column(length: 500)]
    #[Assert\NotBlank]
    private ?string $descriptionCourte = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Assert\NotBlank]
    private ?string $descriptionDetaillee = null;

    // 2) Contenu pédagogique
    #[ORM\Column(type: Types::TEXT)]
    private ?string $objectifsPedagogiques = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $prerequis = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $programme = null;

    #[ORM\Column]
    #[Assert\Positive]
    private ?int $duree = null; // en heures

    #[ORM\Column]
    #[Assert\Positive]
    private ?int $nombreLecons = null;

    // 3) Format
    #[ORM\Column(length: 50)]
    #[Assert\Choice(choices: ['videos_enregistrees', 'live', 'presentiel', 'mixte'])]
    private ?string $format = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dateDebut = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $planning = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $lienLive = null;

    #[ORM\Column(nullable: true)]
    private ?int $nombreSeances = null;

    // 4) Prix & accès
    #[ORM\Column(length: 50)]
    #[Assert\Choice(choices: ['gratuit', 'payant'])]
    private ?string $typeAcces = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
    private ?string $prix = null;

    #[ORM\Column(length: 50, nullable: true)]
    #[Assert\Choice(choices: ['acces_vie', 'acces_3mois', 'par_seance'])]
    private ?string $typeAchat = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
    private ?string $prixPromo = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dateFinPromo = null;

    // 5) Médias
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $imageCouverture = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $videoPromo = null;

    // 6) Formateur
    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'formations')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $formateur = null;

    // 7) Validation & publication
    #[ORM\Column(length: 50)]
    private ?string $statut = 'brouillon'; // brouillon, en_attente, publiee, refusee

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $dateCreation = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $datePublication = null;

    // Bonus
    #[ORM\Column]
    private ?bool $certificat = false;

    #[ORM\Column]
    private ?bool $hasQuiz = false; // RENOMMÉ : booléen indiquant si la formation a des quiz

    #[ORM\Column]
    private ?bool $fichiersTelechargeables = false;

    #[ORM\Column]
    private ?bool $forum = false;

    // Relations
    #[ORM\OneToMany(mappedBy: 'formation', targetEntity: Lecon::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['ordre' => 'ASC'])]
    private Collection $lecons;

    #[ORM\OneToMany(mappedBy: 'formation', targetEntity: Quiz::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $quizzes;

    #[ORM\OneToMany(mappedBy: 'formation', targetEntity: Inscription::class, orphanRemoval: true)]
    private Collection $inscriptions;

    #[ORM\OneToMany(mappedBy: 'formation', targetEntity: Favori::class, cascade: ['remove'], orphanRemoval: true)]
    private Collection $favoris;

    public function __construct()
    {
        $this->lecons = new ArrayCollection();
        $this->quizzes = new ArrayCollection();
        $this->inscriptions = new ArrayCollection();
        $this->favoris = new ArrayCollection();
        $this->dateCreation = new \DateTime();
        $this->statut = 'brouillon';
    }

    // Getters et Setters...
    public function getId(): ?int
    {
        return $this->id;
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

    public function getCategorie(): ?string
    {
        return $this->categorie;
    }

    public function setCategorie(string $categorie): static
    {
        $this->categorie = $categorie;
        return $this;
    }

    public function getNiveau(): ?string
    {
        return $this->niveau;
    }

    public function setNiveau(string $niveau): static
    {
        $this->niveau = $niveau;
        return $this;
    }

    public function getLangue(): ?string
    {
        return $this->langue;
    }

    public function setLangue(string $langue): static
    {
        $this->langue = $langue;
        return $this;
    }

    public function getDescriptionCourte(): ?string
    {
        return $this->descriptionCourte;
    }

    public function setDescriptionCourte(string $descriptionCourte): static
    {
        $this->descriptionCourte = $descriptionCourte;
        return $this;
    }

    public function getDescriptionDetaillee(): ?string
    {
        return $this->descriptionDetaillee;
    }

    public function setDescriptionDetaillee(string $descriptionDetaillee): static
    {
        $this->descriptionDetaillee = $descriptionDetaillee;
        return $this;
    }

    public function getObjectifsPedagogiques(): ?string
    {
        return $this->objectifsPedagogiques;
    }

    public function setObjectifsPedagogiques(string $objectifsPedagogiques): static
    {
        $this->objectifsPedagogiques = $objectifsPedagogiques;
        return $this;
    }

    public function getPrerequis(): ?string
    {
        return $this->prerequis;
    }

    public function setPrerequis(?string $prerequis): static
    {
        $this->prerequis = $prerequis;
        return $this;
    }

    public function getProgramme(): ?string
    {
        return $this->programme;
    }

    public function setProgramme(string $programme): static
    {
        $this->programme = $programme;
        return $this;
    }

    public function getDuree(): ?int
    {
        return $this->duree;
    }

    public function setDuree(int $duree): static
    {
        $this->duree = $duree;
        return $this;
    }

    public function getNombreLecons(): ?int
    {
        return $this->nombreLecons;
    }

    public function setNombreLecons(int $nombreLecons): static
    {
        $this->nombreLecons = $nombreLecons;
        return $this;
    }

    public function getFormat(): ?string
    {
        return $this->format;
    }

    public function setFormat(string $format): static
    {
        $this->format = $format;
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

    public function getPlanning(): ?string
    {
        return $this->planning;
    }

    public function setPlanning(?string $planning): static
    {
        $this->planning = $planning;
        return $this;
    }

    public function getLienLive(): ?string
    {
        return $this->lienLive;
    }

    public function setLienLive(?string $lienLive): static
    {
        $this->lienLive = $lienLive;
        return $this;
    }

    public function getNombreSeances(): ?int
    {
        return $this->nombreSeances;
    }

    public function setNombreSeances(?int $nombreSeances): static
    {
        $this->nombreSeances = $nombreSeances;
        return $this;
    }

    public function getTypeAcces(): ?string
    {
        return $this->typeAcces;
    }

    public function setTypeAcces(string $typeAcces): static
    {
        $this->typeAcces = $typeAcces;
        return $this;
    }

    public function getPrix(): ?string
    {
        return $this->prix;
    }

    public function setPrix(?string $prix): static
    {
        $this->prix = $prix;
        return $this;
    }

    public function getTypeAchat(): ?string
    {
        return $this->typeAchat;
    }

    public function setTypeAchat(?string $typeAchat): static
    {
        $this->typeAchat = $typeAchat;
        return $this;
    }

    public function getPrixPromo(): ?string
    {
        return $this->prixPromo;
    }

    public function setPrixPromo(?string $prixPromo): static
    {
        $this->prixPromo = $prixPromo;
        return $this;
    }

    public function getDateFinPromo(): ?\DateTimeInterface
    {
        return $this->dateFinPromo;
    }

    public function setDateFinPromo(?\DateTimeInterface $dateFinPromo): static
    {
        $this->dateFinPromo = $dateFinPromo;
        return $this;
    }

    public function getImageCouverture(): ?string
    {
        return $this->imageCouverture;
    }

    public function setImageCouverture(?string $imageCouverture): static
    {
        $this->imageCouverture = $imageCouverture;
        return $this;
    }

    public function getVideoPromo(): ?string
    {
        return $this->videoPromo;
    }

    public function setVideoPromo(?string $videoPromo): static
    {
        $this->videoPromo = $videoPromo;
        return $this;
    }

    public function getFormateur(): ?User
    {
        return $this->formateur;
    }

    public function setFormateur(?User $formateur): static
    {
        $this->formateur = $formateur;
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

    public function getDatePublication(): ?\DateTimeInterface
    {
        return $this->datePublication;
    }

    public function setDatePublication(?\DateTimeInterface $datePublication): static
    {
        $this->datePublication = $datePublication;
        return $this;
    }

    public function isCertificat(): ?bool
    {
        return $this->certificat;
    }

    public function setCertificat(bool $certificat): static
    {
        $this->certificat = $certificat;
        return $this;
    }

    // RENOMMÉ : hasQuiz au lieu de quiz
    public function hasQuiz(): ?bool
    {
        return $this->hasQuiz;
    }

    public function setHasQuiz(bool $hasQuiz): static
    {
        $this->hasQuiz = $hasQuiz;
        return $this;
    }

    public function isFichiersTelechargeables(): ?bool
    {
        return $this->fichiersTelechargeables;
    }

    public function setFichiersTelechargeables(bool $fichiersTelechargeables): static
    {
        $this->fichiersTelechargeables = $fichiersTelechargeables;
        return $this;
    }

    public function isForum(): ?bool
    {
        return $this->forum;
    }

    public function setForum(bool $forum): static
    {
        $this->forum = $forum;
        return $this;
    }

    public function getLecons(): Collection
    {
        return $this->lecons;
    }

    public function addLecon(Lecon $lecon): static
    {
        if (!$this->lecons->contains($lecon)) {
            $this->lecons->add($lecon);
            $lecon->setFormation($this);
        }
        return $this;
    }

    public function removeLecon(Lecon $lecon): static
    {
        if ($this->lecons->removeElement($lecon)) {
            if ($lecon->getFormation() === $this) {
                $lecon->setFormation(null);
            }
        }
        return $this;
    }

    // Méthodes pour la collection de Quiz (renommée en quizzes)
    /**
     * @return Collection<int, Quiz>
     */
    public function getQuizzes(): Collection
    {
        return $this->quizzes;
    }

    public function addQuiz(Quiz $quiz): static
    {
        if (!$this->quizzes->contains($quiz)) {
            $this->quizzes->add($quiz);
            $quiz->setFormation($this);
        }
        return $this;
    }

    public function removeQuiz(Quiz $quiz): static
    {
        if ($this->quizzes->removeElement($quiz)) {
            if ($quiz->getFormation() === $this) {
                $quiz->setFormation(null);
            }
        }
        return $this;
    }

    /**
     * @return Collection<int, Inscription>
     */
    public function getInscriptions(): Collection
    {
        return $this->inscriptions;
    }

    public function addInscription(Inscription $inscription): static
    {
        if (!$this->inscriptions->contains($inscription)) {
            $this->inscriptions->add($inscription);
            $inscription->setFormation($this);
        }
        return $this;
    }

    public function removeInscription(Inscription $inscription): static
    {
        if ($this->inscriptions->removeElement($inscription)) {
            if ($inscription->getFormation() === $this) {
                $inscription->setFormation(null);
            }
        }
        return $this;
    }

    /**
     * @return Collection<int, Favori>
     */
    public function getFavoris(): Collection
    {
        return $this->favoris;
    }

    public function addFavori(Favori $favori): static
    {
        if (!$this->favoris->contains($favori)) {
            $this->favoris->add($favori);
            $favori->setFormation($this);
        }
        return $this;
    }

    public function removeFavori(Favori $favori): static
    {
        if ($this->favoris->removeElement($favori)) {
            if ($favori->getFormation() === $this) {
                $favori->setFormation(null);
            }
        }
        return $this;
    }
}
