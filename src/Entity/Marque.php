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

    #[ORM\ManyToOne(targetEntity: Modele::class, inversedBy: 'marques')]
    #[ORM\JoinColumn(name: 'id_modele', referencedColumnName: 'id_modele', nullable: false)]
    private ?Modele $modele = null;

    #[ORM\OneToMany(mappedBy: 'marque', targetEntity: Vehicule::class)]
    private Collection $vehicules;

    public function __construct()
    {
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

    public function getModele(): ?Modele
    {
        return $this->modele;
    }

    public function setModele(?Modele $modele): static
    {
        $this->modele = $modele;

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
