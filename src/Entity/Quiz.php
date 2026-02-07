<?php

namespace App\Entity;

use App\Repository\QuizRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: QuizRepository::class)]
class Quiz
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Le titre est obligatoire')]
    #[Assert\Length(
        min: 3,
        max: 255,
        minMessage: 'Le titre doit contenir au moins {{ limit }} caractères.',
        maxMessage: 'Le titre ne peut pas dépasser {{ limit }} caractères.'
    )]
    private ?string $titre = null;

    #[ORM\Column(length: 500, nullable: true)]
    #[Assert\Length(max: 500, maxMessage: 'La description ne peut pas dépasser {{ limit }} caractères.')]
    private ?string $description = null;

    #[ORM\Column]
    #[Assert\NotNull(message: 'La durée est obligatoire')]
    #[Assert\Positive(message: 'La durée doit être supérieure à 0')]
    #[Assert\Range(
        min: 5,
        max: 300,
        notInRangeMessage: 'La durée doit être comprise entre 5 et 300 minutes.'
    )]
    private ?int $duree = 30;

    #[ORM\Column]
    #[Assert\NotNull(message: 'La note minimale est obligatoire')]
    #[Assert\Range(
        min: 0,
        max: 100,
        notInRangeMessage: 'La note minimale doit être comprise entre 0 et 100%.'
    )]
    private ?int $noteMinimale = 50;

    #[ORM\ManyToOne(targetEntity: Formation::class, inversedBy: 'quizzes')]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull(message: 'La formation est obligatoire')]
    private ?Formation $formation = null;

    #[ORM\OneToMany(mappedBy: 'quiz', targetEntity: Question::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $questions;

    #[ORM\Column]
    #[Assert\Type('bool')]
    private bool $afficherCorrection = true;

    #[ORM\Column]
    #[Assert\Type('bool')]
    private bool $melanger = true;

    public function __construct()
    {
        $this->questions = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitre(): ?string
    {
        return $this->titre;
    }

    public function setTitre(string $titre): self
    {
        $this->titre = $titre;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;
        return $this;
    }

    public function getDuree(): ?int
    {
        return $this->duree;
    }

    public function setDuree(int $duree): self
    {
        $this->duree = $duree;
        return $this;
    }

    public function getNoteMinimale(): ?int
    {
        return $this->noteMinimale;
    }

    public function setNoteMinimale(int $noteMinimale): self
    {
        $this->noteMinimale = $noteMinimale;
        return $this;
    }

    public function getFormation(): ?Formation
    {
        return $this->formation;
    }

    public function setFormation(?Formation $formation): self
    {
        $this->formation = $formation;
        return $this;
    }

    /**
     * @return Collection<int, Question>
     */
    public function getQuestions(): Collection
    {
        return $this->questions;
    }

    public function addQuestion(Question $question): self
    {
        if (!$this->questions->contains($question)) {
            $this->questions->add($question);
            $question->setQuiz($this);
        }

        return $this;
    }

    public function removeQuestion(Question $question): self
    {
        if ($this->questions->removeElement($question)) {
            // set the owning side to null (unless already changed)
            if ($question->getQuiz() === $this) {
                $question->setQuiz(null);
            }
        }

        return $this;
    }

    public function isAfficherCorrection(): bool
    {
        return $this->afficherCorrection;
    }

    public function setAfficherCorrection(bool $afficherCorrection): self
    {
        $this->afficherCorrection = $afficherCorrection;
        return $this;
    }

    public function isMelanger(): bool
    {
        return $this->melanger;
    }

    public function setMelanger(bool $melanger): self
    {
        $this->melanger = $melanger;
        return $this;
    }
}