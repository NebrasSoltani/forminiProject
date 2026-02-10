<?php

namespace App\Entity;

use App\Repository\FormateurRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: FormateurRepository::class)]
class Formateur
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\OneToOne(inversedBy: 'formateur', cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Assert\NotBlank(message: "La spécialité est obligatoire")]
    private ?string $specialite = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Assert\Length(
    min: 20,
    minMessage: "La biographie doit contenir au moins 20 caractères"
    )]
    private ?string $bio = null;
    #[ORM\Column(nullable: true)]

    #[Assert\Positive(message: "L'expérience doit être un nombre positif")]
    private ?int $experienceAnnees = null;


    #[ORM\Column(length: 255, nullable: true)]
    #[Assert\Url(message: "Lien LinkedIn invalide")]
    private ?string $linkedin = null;


    #[ORM\Column(length: 255, nullable: true)]
    #[Assert\Url(message: "Lien portfolio invalide")]
    private ?string $portfolio = null;


    #[ORM\Column(length: 255, nullable: true)]
    private ?string $cv = null;

    #[ORM\Column(nullable: true)]
    private ?float $noteMoyenne = null;

    

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;
        return $this;
    }

    public function getSpecialite(): ?string
    {
        return $this->specialite;
    }

    public function setSpecialite(?string $specialite): static
    {
        $this->specialite = $specialite;
        return $this;
    }

    public function getBio(): ?string
    {
        return $this->bio;
    }

    public function setBio(?string $bio): static
    {
        $this->bio = $bio;
        return $this;
    }

    public function getExperienceAnnees(): ?int
    {
        return $this->experienceAnnees;
    }

    public function setExperienceAnnees(?int $experienceAnnees): static
    {
        $this->experienceAnnees = $experienceAnnees;
        return $this;
    }

    public function getLinkedin(): ?string
    {
        return $this->linkedin;
    }

    public function setLinkedin(?string $linkedin): static
    {
        $this->linkedin = $linkedin;
        return $this;
    }

    public function getPortfolio(): ?string
    {
        return $this->portfolio;
    }

    public function setPortfolio(?string $portfolio): static
    {
        $this->portfolio = $portfolio;
        return $this;
    }

    public function getCv(): ?string
    {
        return $this->cv;
    }

    public function setCv(?string $cv): static
    {
        $this->cv = $cv;
        return $this;
    }

    public function getNoteMoyenne(): ?float
    {
        return $this->noteMoyenne;
    }

    public function setNoteMoyenne(?float $noteMoyenne): static
    {
        $this->noteMoyenne = $noteMoyenne;
        return $this;
    }

   
}
