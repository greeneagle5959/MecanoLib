<?php

namespace App\Entity;

use App\Entity\Client;
use App\Entity\Role;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Scheb\TwoFactorBundle\Model\Google\TwoFactorInterface;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;




#[ORM\Entity]
#[ORM\Table(name: 'utilisateur')]
class Utilisateur implements UserInterface, PasswordAuthenticatedUserInterface ,TwoFactorInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id_utilisateur', type: 'integer', nullable: false)]
    private int $idUtilisateur;

    #[ORM\Column(name: 'email_utilisateur', type: 'string', length: 255, nullable: false)]
    private string $emailUtilisateur;

    #[ORM\Column(name: 'mdp_utilisateur', type: 'string', length: 255, nullable: false)]
    private string $mdpUtilisateur;

    #[ORM\Column(name: 'auth_2fa', type: 'string', length: 255, nullable: true)]
    private ?string $auth2fa = null;

    #[ORM\Column(name: 'is_2fa', type: 'boolean', nullable: false)]
    private bool $is2fa;

    #[ORM\ManyToOne(targetEntity: Role::class, inversedBy: 'utilisateurs')]
    #[ORM\JoinColumn(name: 'id_role', referencedColumnName: 'id_role', nullable: false)]
    private ?Role $role = null;

    #[ORM\OneToMany(mappedBy: 'utilisateur', targetEntity: Client::class)]
    private Collection $clients;

    #[ORM\OneToMany(mappedBy: 'utilisateur', targetEntity: Garage::class)]
    private Collection $garages;

    public function __construct()
    {
        $this->clients = new ArrayCollection();
        $this->garages = new ArrayCollection();
    }

    public function getIdUtilisateur(): int
    {
        return $this->idUtilisateur;
    }

    public function getEmailUtilisateur(): string
    {
        return $this->emailUtilisateur;
    }

    public function setEmailUtilisateur(string $emailUtilisateur): static
    {
        $this->emailUtilisateur = $emailUtilisateur;

        return $this;
    }

    public function getMdpUtilisateur(): string
    {
        return $this->mdpUtilisateur;
    }

    public function setMdpUtilisateur(string $mdpUtilisateur): static
    {
        $this->mdpUtilisateur = $mdpUtilisateur;

        return $this;
    }

    public function getAuth2fa(): ?string
    {
        return $this->auth2fa;
    }

    public function setAuth2fa(?string $auth2fa): static
    {
        $this->auth2fa = $auth2fa;

        return $this;
    }

    public function getIs2fa(): bool
    {
        return $this->is2fa;
    }

    public function setIs2fa(bool $is2fa): static
    {
        $this->is2fa = $is2fa;

        return $this;
    }

    public function getRole(): ?Role
    {
        return $this->role;
    }

    public function setRole(?Role $role): static
    {
        $this->role = $role;

        return $this;
    }

    public function getClients(): Collection
    {
        return $this->clients;
    }

    public function addClient(Client $client): static
    {
        if (!$this->clients->contains($client)) {
            $this->clients->add($client);
            $client->setUtilisateur($this);
        }

        return $this;
    }

    public function removeClient(Client $client): static
    {
        if ($this->clients->removeElement($client)) {
            if ($client->getUtilisateur() === $this) {
                $client->setUtilisateur(null);
            }
        }

        return $this;
    }

    public function getGarages(): Collection
    {
        return $this->garages;
    }

    public function addGarage(Garage $garage): static
    {
        if (!$this->garages->contains($garage)) {
            $this->garages->add($garage);
            $garage->setUtilisateur($this);
        }

        return $this;
    }

    public function removeGarage(Garage $garage): static
    {
        if ($this->garages->removeElement($garage)) {
            if ($garage->getUtilisateur() === $this) {
                $garage->setUtilisateur(null);
            }
        }

        return $this;
    }
    public function getUserIdentifier(): string { return $this->emailUtilisateur; }
    public function getPassword(): string { return $this->mdpUtilisateur; }

    public function getRoles(): array
    {
        return [$this->role?->getNomRole() ?? 'ROLE_USER'];
    }

    public function eraseCredentials(): void { }

    public function isGoogleAuthenticatorEnabled(): bool
    {
        return $this->is2fa;
    }

    public function getGoogleAuthenticatorSecret(): ?string
    {
        return $this->auth2fa;
    }
    public function getGoogleAuthenticatorUsername(): string
    {
        return $this->emailUtilisateur;
    }

}
