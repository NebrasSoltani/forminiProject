<?php

namespace App\Entity;

use App\Repository\ProgressionLeconRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ProgressionLeconRepository::class)]
class ProgressionLecon
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $apprenant = null;

    #[ORM\ManyToOne(targetEntity: Lecon::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Lecon $lecon = null;

    #[ORM\Column]
    private ?bool $terminee = false;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dateTerminee = null;

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

    public function getLecon(): ?Lecon
    {
        return $this->lecon;
    }

    public function setLecon(?Lecon $lecon): static
    {
        $this->lecon = $lecon;
        return $this;
    }

    public function isTerminee(): ?bool
    {
        return $this->terminee;
    }

    public function setTerminee(bool $terminee): static
    {
        $this->terminee = $terminee;
        return $this;
    }

    public function getDateTerminee(): ?\DateTimeInterface
    {
        return $this->dateTerminee;
    }

    public function setDateTerminee(?\DateTimeInterface $dateTerminee): static
    {
        $this->dateTerminee = $dateTerminee;
        return $this;
    }
}
