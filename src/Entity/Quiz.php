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
    private ?string $titre = null;

    #[ORM\Column(length: 500, nullable: true)]
    private ?string $description = null;

    #[ORM\Column]
    #[Assert\Positive]
    private ?int $duree = 30; // durée en minutes

    #[ORM\Column]
    #[Assert\Range(min: 0, max: 100)]
    private ?int $noteMinimale = 50; // note minimale pour réussir (en %)

    #[ORM\ManyToOne(targetEntity: Formation::class, inversedBy: 'quizzes')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Formation $formation = null;

    #[ORM\OneToMany(mappedBy: 'quiz', targetEntity: Question::class, cascade: ['persist', 'remove'])]
    private Collection $questions;

    #[ORM\Column]
    private ?bool $afficherCorrection = true;

    #[ORM\Column]
    private ?bool $melanger = true; // mélanger les questions

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

    public function setTitre(string $titre): static
    {
        $this->titre = $titre;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;
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

    public function getNoteMinimale(): ?int
    {
        return $this->noteMinimale;
    }

    public function setNoteMinimale(int $noteMinimale): static
    {
        $this->noteMinimale = $noteMinimale;
        return $this;
    }

    public function getFormation(): ?Formation
    {
        return $this->formation;
    }

    public function setFormation(?Formation $formation): static
    {
        $this->formation = $formation;
        return $this;
    }

    public function getQuestions(): Collection
    {
        return $this->questions;
    }

    public function addQuestion(Question $question): static
    {
        if (!$this->questions->contains($question)) {
            $this->questions->add($question);
            $question->setQuiz($this);
        }
        return $this;
    }

    public function removeQuestion(Question $question): static
    {
        if ($this->questions->removeElement($question)) {
            if ($question->getQuiz() === $this) {
                $question->setQuiz(null);
            }
        }
        return $this;
    }

    public function isAfficherCorrection(): ?bool
    {
        return $this->afficherCorrection;
    }

    public function setAfficherCorrection(bool $afficherCorrection): static
    {
        $this->afficherCorrection = $afficherCorrection;
        return $this;
    }

    public function isMelanger(): ?bool
    {
        return $this->melanger;
    }

    public function setMelanger(bool $melanger): static
    {
        $this->melanger = $melanger;
        return $this;
    }
}