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
    #[ORM\Column(name: 'id_marque', type: 'integer', nullable: false)]
    private int $idMarque;

    #[ORM\Column(name: 'nom_marque', type: 'string', length: 50, nullable: false)]
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

    public function getIdMarque(): int
    {
        return $this->idMarque;
    }

    public function getNomMarque(): string
    {
        return $this->nomMarque;
    }

    public function setNomMarque(string $nomMarque): static
    {
        $this->nomMarque = $nomMarque;

        return $this;
    }

    /** @return Collection<int, Modele> */
    public function getModeles(): Collection
    {
        return $this->modeles;
    }

    public function addModele(Modele $modele): static
    {
        if (!$this->modeles->contains($modele)) {
            $this->modeles->add($modele);
            $modele->setMarque($this);
        }

        return $this;
    }

    public function removeModele(Modele $modele): static
    {
        if ($this->modeles->removeElement($modele)) {
            if ($modele->getMarque() === $this) {
                $modele->setMarque(null);
            }
        }

        return $this;
    }

    public function getModele(): ?Modele
    {
        $modele = $this->modeles->first();

        return $modele instanceof Modele ? $modele : null;
    }

    public function setModele(?Modele $modele): static
    {
        foreach ($this->modeles->toArray() as $existingModele) {
            if ($modele === null || $existingModele !== $modele) {
                $this->removeModele($existingModele);
            }
        }

        if ($modele !== null) {
            $this->addModele($modele);
        }

        return $this;
    }

/** @return Collection<int, Vehicule> */
    public function getVehicules(): Collection
    {
        return $this->vehicules;
    }

    public function addVehicule(Vehicule $vehicule): static
    {
        if (!$this->vehicules->contains($vehicule)) {
            $this->vehicules->add($vehicule);
            $vehicule->setMarque($this);
        }

        return $this;
    }

    public function removeVehicule(Vehicule $vehicule): static
    {
        if ($this->vehicules->removeElement($vehicule)) {
            if ($vehicule->getMarque() === $this) {
                $vehicule->setMarque(null);
            }
        }

        return $this;
    }

}
