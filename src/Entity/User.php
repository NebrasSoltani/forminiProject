<?php



namespace App\Entity;



use App\Enum\Gouvernorat;

use App\Repository\UserRepository;

use Doctrine\Common\Collections\ArrayCollection;

use Doctrine\Common\Collections\Collection;

use Doctrine\DBAL\Types\Types;

use Doctrine\ORM\Mapping as ORM;

use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;

use Symfony\Component\Security\Core\User\UserInterface;

use Symfony\Component\Validator\Constraints as Assert;



#[ORM\Entity(repositoryClass: UserRepository::class)]

#[UniqueEntity(fields: ['email'], message: 'Il existe déjà un compte avec cet email')]

class User implements UserInterface, PasswordAuthenticatedUserInterface

{

    #[ORM\Id]

    #[ORM\GeneratedValue]

    #[ORM\Column]

    private ?int $id = null;



    #[ORM\Column(length: 180, unique: true)]

    #[Assert\NotBlank(message: "L'email est obligatoire")]

    #[Assert\Email(message: "Email invalide")]

    private ?string $email = null;



    #[ORM\Column]

    private array $roles = [];



    #[ORM\Column]

    private ?string $password = null;



    #[ORM\Column(length: 100)]

    #[Assert\NotBlank(message: 'Le nom est obligatoire')]

    private ?string $nom = null;



    #[ORM\Column(length: 100)]

    #[Assert\NotBlank(message: 'Le prénom est obligatoire')]

    private ?string $prenom = null;



    #[ORM\Column(length: 12)]

    #[Assert\NotBlank(message: 'Le téléphone est obligatoire')]

    private ?string $telephone = null;



    #[ORM\Column(type: 'string', length: 255, nullable: true, enumType: Gouvernorat::class)]

    private ?Gouvernorat $gouvernorat = null;



    #[ORM\Column(type: Types::DATE_MUTABLE)]

    #[Assert\NotBlank(message: "La date de naissance est obligatoire")]

    #[Assert\LessThan("today", message: "La date doit être dans le passé")]

    private ?\DateTimeInterface $dateNaissance = null;





    #[ORM\Column(length: 100, nullable: true)]

    private ?string $profession = null;



    #[ORM\Column(length: 100, nullable: true)]

    private ?string $niveauEtude = null;



    #[ORM\Column(length: 50)]

    #[Assert\NotBlank(message: "vous devez choisir un role")]

    #[Assert\Choice(choices: ['formateur', 'apprenant', 'societe', 'admin'], message: 'Choisissez un rôle valide')]

    private ?string $roleUtilisateur = null;



    #[ORM\Column(length: 255, nullable: true)]

    private ?string $photo = null;



    #[ORM\Column(options: ['default' => 0])]

    private bool $isEmailVerified = false;



    #[ORM\Column(length: 255, nullable: true)]

    private ?string $emailVerificationToken = null;



    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]

    private ?\DateTimeInterface $emailVerificationTokenExpiresAt = null;



    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]

    private ?\DateTimeInterface $emailVerifiedAt = null;



    #[ORM\OneToMany(mappedBy: 'formateur', targetEntity: Formation::class)]

    private Collection $formations;



    #[ORM\OneToMany(mappedBy: 'apprenant', targetEntity: Inscription::class, cascade: ['remove'], orphanRemoval: true)]

    private Collection $inscriptions;



    #[ORM\OneToMany(mappedBy: 'apprenant', targetEntity: Favori::class, cascade: ['remove'], orphanRemoval: true)]

    private Collection $favoris;



    #[ORM\OneToMany(mappedBy: 'user', targetEntity: ParticipationEvenement::class, cascade: ['remove'], orphanRemoval: true)]

    private Collection $participationEvenements;



    #[ORM\OneToOne(mappedBy: 'user', cascade: ['persist', 'remove'])]

    private ?Formateur $formateur = null;



    #[ORM\OneToOne(mappedBy: 'user', cascade: ['persist', 'remove'])]

    private ?Apprenant $apprenant = null;



    #[ORM\OneToOne(mappedBy: 'user', cascade: ['persist', 'remove'])]

    private ?Societe $societe = null;



    public function __construct()

    {

        $this->formations = new ArrayCollection();

        $this->inscriptions = new ArrayCollection();

        $this->favoris = new ArrayCollection();

        $this->participationEvenements = new ArrayCollection();

    }



    public function getId(): ?int

    {

        return $this->id;

    }



    public function getEmail(): ?string

    {

        return $this->email;

    }



    public function setEmail(?string $email): static

    {

        $this->email = $email;

        return $this;

    }



    public function getUserIdentifier(): string

    {

        return (string) $this->email;

    }



    public function getRoles(): array

    {

        $roles = $this->roles;

        $roles[] = 'ROLE_USER';

        return array_unique($roles);

    }



    public function setRoles(array $roles): static

    {

        $this->roles = $roles;

        return $this;

    }



    public function getPassword(): ?string

    {

        return $this->password;

    }



    public function setPassword(string $password): static

    {

        $this->password = $password;

        return $this;

    }



    public function eraseCredentials(): void

    {

    }



    public function getNom(): ?string

    {

        return $this->nom;

    }



    public function setNom(?string $nom): static

    {

        $this->nom = $nom;

        return $this;

    }



    public function getPrenom(): ?string

    {

        return $this->prenom;

    }



    public function setPrenom(?string $prenom): static

    {

        $this->prenom = $prenom;

        return $this;

    }



    public function getTelephone(): ?string

    {

        return $this->telephone;

    }



    public function setTelephone(?string $telephone): static

    {

        $this->telephone = $telephone;

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



    public function getProfession(): ?string

    {

        return $this->profession;

    }



    public function setProfession(?string $profession): static

    {

        $this->profession = $profession;

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



    public function getRoleUtilisateur(): ?string

    {

        return $this->roleUtilisateur;

    }



    public function setRoleUtilisateur(string $roleUtilisateur): static

    {

        $this->roleUtilisateur = $roleUtilisateur;

        return $this;

    }



    public function getPhoto(): ?string

    {

        return $this->photo;

    }



    public function setPhoto(?string $photo): static

    {

        $this->photo = $photo;

        return $this;

    }



    public function isEmailVerified(): bool

    {

        return $this->isEmailVerified;

    }



    public function setIsEmailVerified(bool $isEmailVerified): static

    {

        $this->isEmailVerified = $isEmailVerified;

        return $this;

    }



    public function getEmailVerificationToken(): ?string

    {

        return $this->emailVerificationToken;

    }



    public function setEmailVerificationToken(?string $emailVerificationToken): static

    {

        $this->emailVerificationToken = $emailVerificationToken;

        return $this;

    }



    public function getEmailVerificationTokenExpiresAt(): ?\DateTimeInterface

    {

        return $this->emailVerificationTokenExpiresAt;

    }



    public function setEmailVerificationTokenExpiresAt(?\DateTimeInterface $emailVerificationTokenExpiresAt): static

    {

        $this->emailVerificationTokenExpiresAt = $emailVerificationTokenExpiresAt;

        return $this;

    }



    public function getEmailVerifiedAt(): ?\DateTimeInterface

    {

        return $this->emailVerifiedAt;

    }



    public function setEmailVerifiedAt(?\DateTimeInterface $emailVerifiedAt): static

    {

        $this->emailVerifiedAt = $emailVerifiedAt;

        return $this;

    }



    /**

     * @return Collection<int, Formation>

     */

    public function getFormations(): Collection

    {

        return $this->formations;

    }



    public function addFormation(Formation $formation): static

    {

        if (!$this->formations->contains($formation)) {

            $this->formations->add($formation);

            $formation->setFormateur($this);

        }

        return $this;

    }



    public function removeFormation(Formation $formation): static

    {

        if ($this->formations->removeElement($formation)) {

            if ($formation->getFormateur() === $this) {

                $formation->setFormateur(null);

            }

        }

        return $this;

    }



    /**

     * @return Collection<int, Inscription>

     */

    public function getInscriptions(): Collection

    {

        return $this->inscriptions;

    }



    public function addInscription(Inscription $inscription): static

    {

        if (!$this->inscriptions->contains($inscription)) {

            $this->inscriptions->add($inscription);

            $inscription->setApprenant($this);

        }

        return $this;

    }



    public function removeInscription(Inscription $inscription): static

    {

        if ($this->inscriptions->removeElement($inscription)) {

            if ($inscription->getApprenant() === $this) {

                $inscription->setApprenant(null);

            }

        }

        return $this;

    }



    /**

     * @return Collection<int, \App\Entity\ParticipationEvenement>

     */

    public function getParticipationEvenements(): Collection

    {

        return $this->participationEvenements;

    }



    public function addParticipationEvenement(\App\Entity\ParticipationEvenement $participationEvenement): static

    {

        if (!$this->participationEvenements->contains($participationEvenement)) {

            $this->participationEvenements->add($participationEvenement);

            $participationEvenement->setUser($this);

        }

        return $this;

    }



    public function removeParticipationEvenement(\App\Entity\ParticipationEvenement $participationEvenement): static

    {

        if ($this->participationEvenements->removeElement($participationEvenement)) {

            if ($participationEvenement->getUser() === $this) {

                $participationEvenement->setUser(null);

            }

        }

        return $this;

    }



    /**

     * @return Collection<int, Favori>

     */

    public function getFavoris(): Collection

    {

        return $this->favoris;

    }



    public function addFavori(Favori $favori): static

    {

        if (!$this->favoris->contains($favori)) {

            $this->favoris->add($favori);

            $favori->setApprenant($this);

        }

        return $this;

    }



    public function removeFavori(Favori $favori): static

    {

        if ($this->favoris->removeElement($favori)) {

            if ($favori->getApprenant() === $this) {

                $favori->setApprenant(null);

            }

        }

        return $this;

    }



    public function getFormateur(): ?Formateur

    {

        return $this->formateur;

    }



    public function setFormateur(?Formateur $formateur): static

    {

        // unset the owning side of the relation if necessary

        if ($formateur === null && $this->formateur !== null) {

            $this->formateur->setUser(null);

        }



        // set the owning side of the relation if necessary

        if ($formateur !== null && $formateur->getUser() !== $this) {

            $formateur->setUser($this);

        }



        $this->formateur = $formateur;

        return $this;

    }



    public function getApprenant(): ?Apprenant

    {

        return $this->apprenant;

    }



    public function setApprenant(?Apprenant $apprenant): static

    {

        // unset the owning side of the relation if necessary

        if ($apprenant === null && $this->apprenant !== null) {

            $this->apprenant->setUser(null);

        }



        // set the owning side of the relation if necessary

        if ($apprenant !== null && $apprenant->getUser() !== $this) {

            $apprenant->setUser($this);

        }



        $this->apprenant = $apprenant;

        return $this;

    }



    public function getSociete(): ?Societe

    {

        return $this->societe;

    }



    public function setSociete(?Societe $societe): static

    {

        // unset the owning side of the relation if necessary

        if ($societe === null && $this->societe !== null) {

            $this->societe->setUser(null);

        }



        // set the owning side of the relation if necessary

        if ($societe !== null && $societe->getUser() !== $this) {

            $societe->setUser($this);

        }



        $this->societe = $societe;

        return $this;

    }



    public function getGouvernorat(): ?Gouvernorat

{

    return $this->gouvernorat;

}



public function setGouvernorat(?Gouvernorat $gouvernorat): static

{

    $this->gouvernorat = $gouvernorat;

    return $this;

}

}

