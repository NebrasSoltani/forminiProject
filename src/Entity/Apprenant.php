<?php

namespace App\Entity;

use App\Repository\ApprenantRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints\Collection;

#[ORM\Entity(repositoryClass: ApprenantRepository::class)]
class Apprenant
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\OneToOne(inversedBy: 'apprenant', cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $niveauEtude = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dateNaissance = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $genre = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $etatCivil = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $objectif = null;

    #[ORM\ManyToOne(targetEntity: Domaine::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?Domaine $domaine = null;


    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $domainesInteret = [];


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

    public function getNiveauEtude(): ?string
    {
        return $this->niveauEtude;
    }

    public function setNiveauEtude(?string $niveauEtude): static
    {
        $this->niveauEtude = $niveauEtude;
        return $this;
    }

    public function getDateNaissance(): ?\DateTimeInterface
    {
        return $this->dateNaissance;
    }

    public function setDateNaissance(?\DateTimeInterface $dateNaissance): static
    {
        $this->dateNaissance = $dateNaissance;
        return $this;
    }

    public function getGenre(): ?string
    {
        return $this->genre;
    }

    public function setGenre(?string $genre): static
    {
        $this->genre = $genre;
        return $this;
    }

    public function getEtatCivil(): ?string
    {
        return $this->etatCivil;
    }

    public function setEtatCivil(?string $etatCivil): static
    {
        $this->etatCivil = $etatCivil;
        return $this;
    }

    public function getObjectif(): ?string
    {
        return $this->objectif;
    }

    public function setObjectif(?string $objectif): static
    {
        $this->objectif = $objectif;
        return $this;
    }
   public function getDomaine(): ?Domaine
{
    return $this->domaine;
}

public function setDomaine(?Domaine $domaine): static
{
    $this->domaine = $domaine;
    return $this;
}

public function getDomainesInteret(): array
{
    return $this->domainesInteret ?? [];
}

public function setDomainesInteret(array $domainesInteret): static
{
    $this->domainesInteret = $domainesInteret;
    return $this;
}

}
