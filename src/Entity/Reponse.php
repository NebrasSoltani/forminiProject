<?php

namespace App\Entity;

use App\Repository\ReponseRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ReponseRepository::class)]
class Reponse
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 500)]
    #[Assert\NotBlank(message: 'Le texte de la réponse est obligatoire')]
    #[Assert\Length(
        min: 1,
        max: 500,
        maxMessage: 'Le texte de la réponse ne peut pas dépasser {{ limit }} caractères.'
    )]
    private ?string $texte = null;

    #[ORM\Column]
    private bool $estCorrecte = false;

    #[ORM\ManyToOne(targetEntity: Question::class, inversedBy: 'reponses')]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull]
    private ?Question $question = null;

    // ⭐ NOUVEAU CHAMP POUR LE CHATBOT
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Assert\Length(max: 2000, maxMessage: 'L\'explication de la réponse ne peut pas dépasser {{ limit }} caractères.')]
    private ?string $explicationReponse = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTexte(): ?string
    {
        return $this->texte;
    }

    public function setTexte(string $texte): self
    {
        $this->texte = $texte;
        return $this;
    }

    public function isEstCorrecte(): bool
    {
        return $this->estCorrecte;
    }

    public function setEstCorrecte(bool $estCorrecte): self
    {
        $this->estCorrecte = $estCorrecte;
        return $this;
    }

    public function getQuestion(): ?Question
    {
        return $this->question;
    }

    public function setQuestion(?Question $question): self
    {
        $this->question = $question;
        return $this;
    }

    // ⭐ GETTER ET SETTER POUR LE NOUVEAU CHAMP
    public function getExplicationReponse(): ?string
    {
        return $this->explicationReponse;
    }

    public function setExplicationReponse(?string $explicationReponse): self
    {
        $this->explicationReponse = $explicationReponse;
        return $this;
    }
}
