<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'ville')]
class Ville
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id_ville', type: 'integer', nullable: false)]
    private int $idVille;

    #[ORM\Column(name: 'nom_ville', type: 'string', length: 30, nullable: false)]
    private string $nomVille;

    #[ORM\Column(name: 'code_postal', type: 'string', length: 10, nullable: false)]
    private string $codePostal;

    #[ORM\Column(name: 'code_inssee', type: 'string', length: 10, nullable: false)]
    private string $codeInssee;

    #[ORM\OneToMany(mappedBy: 'ville', targetEntity: Garage::class)]
    private Collection $garages;

    public function __construct()
    {
        $this->garages = new ArrayCollection();
    }

    public function getIdVille(): int
    {
        return $this->idVille;
    }

    public function getNomVille(): string
    {
        return $this->nomVille;
    }

    public function setNomVille(string $nomVille): static
    {
        $this->nomVille = $nomVille;

        return $this;
    }

    public function getCodePostal(): string
    {
        return $this->codePostal;
    }

    public function setCodePostal(string $codePostal): static
    {
        $this->codePostal = $codePostal;

        return $this;
    }

    public function getCodeInssee(): string
    {
        return $this->codeInssee;
    }

    public function setCodeInssee(string $codeInssee): static
    {
        $this->codeInssee = $codeInssee;

        return $this;
    }

/** @return Collection<int, Garage> */
    public function getGarages(): Collection
    {
        return $this->garages;
    }

    public function addGarage(Garage $garage): static
    {
        if (!$this->garages->contains($garage)) {
            $this->garages->add($garage);
            $garage->setVille($this);
        }

        return $this;
    }

    public function removeGarage(Garage $garage): static
    {
        if ($this->garages->removeElement($garage)) {
            if ($garage->getVille() === $this) {
                $garage->setVille(null);
            }
        }

        return $this;
    }

}
