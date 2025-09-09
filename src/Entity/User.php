<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;

#[ORM\Entity(repositoryClass: UserRepository::class)]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 55)]
    private ?string $nom = null;

    #[ORM\Column(length: 55)]
    private ?string $prenom = null;

    #[ORM\Column(length: 55)]
    private ?string $email = null;

    #[ORM\Column(length: 255)]
    private ?string $password = null;

    #[ORM\Column(length: 10, nullable: true)]
    private ?string $telephone = null;

    #[ORM\Column(nullable: true)]
    private ?int $animalPoss = null;

    #[ORM\Column(length: 50)]
    private ?string $userRole = 'ROLE_USER';

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(string $nom): static
    {
        $this->nom = $nom;

        return $this;
    }

    public function getPrenom(): ?string
    {
        return $this->prenom;
    }

    public function setPrenom(string $prenom): static
    {
        $this->prenom = $prenom;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

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

    public function getTelephone(): ?string
    {
        return $this->telephone;
    }

    public function setTelephone(?string $telephone): static
    {
        $this->telephone = $telephone;

        return $this;
    }

    public function getAnimalPoss(): ?int
    {
        return $this->animalPoss;
    }

    public function setAnimalPoss(?int $animalPoss): static
    {
        $this->animalPoss = $animalPoss;

        return $this;
    }

public function getRoles(): array
{
    return [$this->userRole ?? 'ROLE_USER'];
}

public function getUserIdentifier(): string
{
    return $this->email; // ou autre champ unique
}

public function eraseCredentials(): void
{
    // Efface les données sensibles, ex : $this->plainPassword = null;
}

public function getUserRole(): ?string
{
    return $this->userRole;
}

public function setUserRole(string $userRole): static
{
    $this->userRole = $userRole;

    return $this;
}


#[ORM\OneToMany(mappedBy: 'user', targetEntity: Reservation::class, orphanRemoval: true)]
private Collection $reservations;

public function __construct()
{
    $this->reservations = new ArrayCollection();
}

/**
 * @return Collection<int, Reservation>
 */
public function getReservations(): Collection
{
    return $this->reservations;
}

public function addReservation(Reservation $reservation): self
{
    if (!$this->reservations->contains($reservation)) {
        $this->reservations->add($reservation);
        $reservation->setUser($this);
    }

    return $this;
}

public function removeReservation(Reservation $reservation): self
{
    if ($this->reservations->removeElement($reservation)) {
        // Si la réservation appartient à l'utilisateur, on supprime la liaison
        if ($reservation->getUser() === $this) {
            $reservation->setUser(null);
        }
    }

    return $this;
}



}
