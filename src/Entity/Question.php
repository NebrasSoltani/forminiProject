<?php

namespace App\Entity;

use App\Repository\QuestionRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: QuestionRepository::class)]
class Question
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Assert\NotBlank(message: 'L\'énoncé de la question est obligatoire')]
    #[Assert\Length(
        min: 8,
        minMessage: 'L\'énoncé doit contenir au moins {{ limit }} caractères.'
    )]
    private ?string $enonce = null;

    #[ORM\Column(length: 50)]
    #[Assert\NotBlank(message: 'Le type de question est obligatoire')]
    #[Assert\Choice(
        choices: ['qcm', 'vrai_faux', 'texte'],
        message: 'Choisissez un type valide : qcm, vrai_faux ou texte.'
    )]
    private string $type = 'qcm';

    #[ORM\Column]
    #[Assert\NotNull(message: 'Le nombre de points est obligatoire')]
    #[Assert\Positive(message: 'Le nombre de points doit être positif')]
    #[Assert\Range(min: 1, max: 100)]
    private ?int $points = 1;

    #[ORM\Column]
    #[Assert\NotNull]
    #[Assert\PositiveOrZero]
    private ?int $ordre = 1;

    #[ORM\ManyToOne(targetEntity: Quiz::class, inversedBy: 'questions')]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull]
    private ?Quiz $quiz = null;

    #[ORM\OneToMany(mappedBy: 'question', targetEntity: Reponse::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $reponses;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Assert\Length(max: 3000, maxMessage: 'L\'explication ne peut pas dépasser {{ limit }} caractères.')]
    private ?string $explication = null;

    public function __construct()
    {
        $this->reponses = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEnonce(): ?string
    {
        return $this->enonce;
    }

    public function setEnonce(string $enonce): self
    {
        $this->enonce = $enonce;
        return $this;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): self
    {
        $this->type = $type;
        return $this;
    }

    public function getPoints(): ?int
    {
        return $this->points;
    }

    public function setPoints(int $points): self
    {
        $this->points = $points;
        return $this;
    }

    public function getOrdre(): ?int
    {
        return $this->ordre;
    }

    public function setOrdre(int $ordre): self
    {
        $this->ordre = $ordre;
        return $this;
    }

    public function getQuiz(): ?Quiz
    {
        return $this->quiz;
    }

    public function setQuiz(?Quiz $quiz): self
    {
        $this->quiz = $quiz;
        return $this;
    }

    /**
     * @return Collection<int, Reponse>
     */
    public function getReponses(): Collection
    {
        return $this->reponses;
    }

    public function addReponse(Reponse $reponse): self
    {
        if (!$this->reponses->contains($reponse)) {
            $this->reponses->add($reponse);
            $reponse->setQuestion($this);
        }

        return $this;
    }

    public function removeReponse(Reponse $reponse): self
    {
        if ($this->reponses->removeElement($reponse)) {
            // set the owning side to null (unless already changed)
            if ($reponse->getQuestion() === $this) {
                $reponse->setQuestion(null);
            }
        }

        return $this;
    }

    public function getExplication(): ?string
    {
        return $this->explication;
    }

    public function setExplication(?string $explication): self
    {
        $this->explication = $explication;
        return $this;
    }

    // Méthode métier utile
    public function hasAtLeastOneCorrectAnswer(): bool
    {
        if ($this->type === 'texte') {
            return true;
        }

        foreach ($this->reponses as $reponse) {
            if ($reponse->isEstCorrecte()) {
                return true;
            }
        }

        return false;
    }
}
