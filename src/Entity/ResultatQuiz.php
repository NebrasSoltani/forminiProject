<?php

namespace App\Entity;

use App\Repository\ResultatQuizRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ResultatQuizRepository::class)]
class ResultatQuiz
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $apprenant = null;

    #[ORM\ManyToOne(targetEntity: Quiz::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Quiz $quiz = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 5, scale: 2)]
    private ?string $note = null; // note en %

    #[ORM\Column]
    private ?int $nombreBonnesReponses = null;

    #[ORM\Column]
    private ?int $nombreTotalQuestions = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $dateTentative = null;

    #[ORM\Column]
    private ?bool $reussi = false;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $detailsReponses = null; // JSON des réponses

    public function __construct()
    {
        $this->dateTentative = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getQuiz(): ?Quiz
    {
        return $this->quiz;
    }

    public function setQuiz(?Quiz $quiz): static
    {
        $this->quiz = $quiz;
        return $this;
    }

    public function getNote(): ?string
    {
        return $this->note;
    }

    public function setNote(string $note): static
    {
        $this->note = $note;
        return $this;
    }

    public function getNombreBonnesReponses(): ?int
    {
        return $this->nombreBonnesReponses;
    }

    public function setNombreBonnesReponses(int $nombreBonnesReponses): static
    {
        $this->nombreBonnesReponses = $nombreBonnesReponses;
        return $this;
    }

    public function getNombreTotalQuestions(): ?int
    {
        return $this->nombreTotalQuestions;
    }

    public function setNombreTotalQuestions(int $nombreTotalQuestions): static
    {
        $this->nombreTotalQuestions = $nombreTotalQuestions;
        return $this;
    }

    public function getDateTentative(): ?\DateTimeInterface
    {
        return $this->dateTentative;
    }

    public function setDateTentative(\DateTimeInterface $dateTentative): static
    {
        $this->dateTentative = $dateTentative;
        return $this;
    }

    public function isReussi(): ?bool
    {
        return $this->reussi;
    }

    public function setReussi(bool $reussi): static
    {
        $this->reussi = $reussi;
        return $this;
    }

    public function getDetailsReponses(): ?string
    {
        return $this->detailsReponses;
    }

    public function setDetailsReponses(?string $detailsReponses): static
    {
        $this->detailsReponses = $detailsReponses;
        return $this;
    }
}