<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'marque')]
class Marque
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id_marque', type: 'integer')]
    private ?int $idMarque = null;

    #[ORM\Column(name: 'nom_marque', type: 'string', length: 50)]
    private string $nomMarque;

    #[ORM\OneToMany(mappedBy: 'marque', targetEntity: Modele::class)]
    private Collection $modeles;

    #[ORM\OneToMany(mappedBy: 'marque', targetEntity: Vehicule::class)]
    private Collection $vehicules;

    public function __construct()
    {
        $this->modeles = new ArrayCollection();
        $this->vehicules = new ArrayCollection();
    }

    public function getIdMarque(): ?int
    {
        return $this->idMarque;
    }

    public function getNomMarque(): string
    {
        return $this->nomMarque;
    }

    public function setNomMarque(string $nomMarque): self
    {
        $this->nomMarque = $nomMarque;
        return $this;
    }

    public function getModeles(): Collection
    {
        return $this->modeles;
    }

    public function addModele(Modele $modele): self
    {
        if (!$this->modeles->contains($modele)) {
            $this->modeles[] = $modele;
            $modele->setMarque($this);
        }

        return $this;
    }

    public function removeModele(Modele $modele): self
    {
        if ($this->modeles->removeElement($modele)) {
            if ($modele->getMarque() === $this) {
                $modele->setMarque(null);
            }
        }

        return $this;
    }

/** @return Collection<int, Vehicule> */
    public function getVehicules(): Collection
    {
        return $this->vehicules;
    }

    public function addVehicule(Vehicule $vehicule): self
    {
        if (!$this->vehicules->contains($vehicule)) {
            $this->vehicules->add($vehicule);
            $vehicule->setMarque($this);
        }

        return $this;
    }

    public function removeVehicule(Vehicule $vehicule): self
    {
        if ($this->vehicules->removeElement($vehicule)) {
            if ($vehicule->getMarque() === $this) {
                $vehicule->setMarque(null);
            }
        }

        return $this;
    }
}