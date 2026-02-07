<?php

namespace App\Entity;

use App\Repository\ResultatQuizRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ResultatQuizRepository::class)]
class ResultatQuiz
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull]
    private ?User $apprenant = null;

    #[ORM\ManyToOne(targetEntity: Quiz::class)]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull]
    private ?Quiz $quiz = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 5, scale: 2)]
    #[Assert\NotNull]
    #[Assert\Range(min: 0, max: 100)]
    private ?string $note = null;

    #[ORM\Column]
    #[Assert\NotNull]
    #[Assert\PositiveOrZero]
    private ?int $nombreBonnesReponses = null;

    #[ORM\Column]
    #[Assert\NotNull]
    #[Assert\Positive]
    private ?int $nombreTotalQuestions = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Assert\NotNull]
    private ?\DateTimeInterface $dateTentative = null;

    #[ORM\Column]
    private bool $reussi = false;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $detailsReponses = null;

    public function __construct()
    {
        $this->dateTentative = new \DateTime();
    }

    // Getters & Setters ...

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getApprenant(): ?User
    {
        return $this->apprenant;
    }

    public function setApprenant(?User $apprenant): self
    {
        $this->apprenant = $apprenant;
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

    public function getNote(): ?string
    {
        return $this->note;
    }

    public function setNote(string $note): self
    {
        $this->note = $note;
        return $this;
    }

    public function getNombreBonnesReponses(): ?int
    {
        return $this->nombreBonnesReponses;
    }

    public function setNombreBonnesReponses(int $nombreBonnesReponses): self
    {
        $this->nombreBonnesReponses = $nombreBonnesReponses;
        return $this;
    }

    public function getNombreTotalQuestions(): ?int
    {
        return $this->nombreTotalQuestions;
    }

    public function setNombreTotalQuestions(int $nombreTotalQuestions): self
    {
        $this->nombreTotalQuestions = $nombreTotalQuestions;
        return $this;
    }

    public function getDateTentative(): ?\DateTimeInterface
    {
        return $this->dateTentative;
    }

    public function setDateTentative(\DateTimeInterface $dateTentative): self
    {
        $this->dateTentative = $dateTentative;
        return $this;
    }

    public function isReussi(): bool
    {
        return $this->reussi;
    }

    public function setReussi(bool $reussi): self
    {
        $this->reussi = $reussi;
        return $this;
    }

    public function getDetailsReponses(): ?string
    {
        return $this->detailsReponses;
    }

    public function setDetailsReponses(?string $detailsReponses): self
    {
        $this->detailsReponses = $detailsReponses;
        return $this;
    }
}