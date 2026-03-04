<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'role')]
class Role
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id_role', type: 'integer', nullable: false)]
    private int $idRole;

    #[ORM\Column(name: 'nom_role', type: 'string', length: 30, nullable: false)]
    private string $nomRole;

    #[ORM\OneToMany(mappedBy: 'role', targetEntity: Utilisateur::class)]
    private Collection $utilisateurs;

    public function __construct()
    {
        $this->utilisateurs = new ArrayCollection();
    }

    public function getIdRole(): int
    {
        return $this->idRole;
    }

    public function getNomRole(): string
    {
        return $this->nomRole;
    }

    public function setNomRole(string $nomRole): static
    {
        $this->nomRole = $nomRole;

        return $this;
    }

/** @return Collection<int, Utilisateur> */
    public function getUtilisateurs(): Collection
    {
        return $this->utilisateurs;
    }

    public function addUtilisateur(Utilisateur $utilisateur): static
    {
        if (!$this->utilisateurs->contains($utilisateur)) {
            $this->utilisateurs->add($utilisateur);
            $utilisateur->setRole($this);
        }

        return $this;
    }

    public function removeUtilisateur(Utilisateur $utilisateur): static
    {
        if ($this->utilisateurs->removeElement($utilisateur)) {
            if ($utilisateur->getRole() === $this) {
                $utilisateur->setRole(null);
            }
        }

        return $this;
    }

}
