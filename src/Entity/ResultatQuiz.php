<?php

namespace App\Entity;

use App\Repository\ResultatQuizRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ResultatQuizRepository::class)]
class ResultatQuiz
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'resultats')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Quiz $quiz = null;

    #[ORM\Column(nullable: false)]
    private \DateTimeImmutable $dateRealisation;

    #[ORM\Column(type: 'float', precision: 6, scale: 2)]
    private float $scoreObtenu = 0.00;

    #[ORM\Column(nullable: false)]
    private int $nbQuestionsRepondues = 0;

    #[ORM\Column(nullable: true)]
    private ?int $tempsPrisSecondes = null;

    #[ORM\Column(length: 50)]
    private string $statut = 'termine';

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $reponsesDetaillees = null;

    public function __construct()
    {
        $this->dateRealisation = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getDateRealisation(): \DateTimeImmutable
    {
        return $this->dateRealisation;
    }

    public function getScoreObtenu(): float
    {
        return $this->scoreObtenu;
    }

    public function setScoreObtenu(float $scoreObtenu): self
    {
        $this->scoreObtenu = max(0.0, $scoreObtenu); // protection basique
        return $this;
    }

    public function getNbQuestionsRepondues(): int
    {
        return $this->nbQuestionsRepondues;
    }

    public function setNbQuestionsRepondues(int $nbQuestionsRepondues): self
    {
        $this->nbQuestionsRepondues = max(0, $nbQuestionsRepondues);
        return $this;
    }

    public function getTempsPrisSecondes(): ?int
    {
        return $this->tempsPrisSecondes;
    }

    public function setTempsPrisSecondes(?int $tempsPrisSecondes): self
    {
        $this->tempsPrisSecondes = $tempsPrisSecondes;
        return $this;
    }

    public function getStatut(): string
    {
        return $this->statut;
    }

    public function setStatut(string $statut): self
    {
        $this->statut = $statut;
        return $this;
    }

    public function getReponsesDetaillees(): ?array
    {
        return $this->reponsesDetaillees;
    }

    public function setReponsesDetaillees(?array $reponsesDetaillees): self
    {
        $this->reponsesDetaillees = $reponsesDetaillees;
        return $this;
    }
}