<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'prestation')]
class Prestation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id_prestation', type: 'integer', nullable: false)]
    private int $idPrestation;

    #[ORM\Column(name: 'nom_prestation', type: 'string', length: 100, nullable: false)]
    private string $nomPrestation;

    #[ORM\Column(name: 'description_prestation', type: 'text', nullable: false)]
    private string $descriptionPrestation;

    #[ORM\Column(name: 'duree_prestation', type: 'string', length: 10, nullable: false)]
    private string $dureePrestation;

    #[ORM\ManyToOne(targetEntity: Categorie::class, inversedBy: 'prestations')]
    #[ORM\JoinColumn(name: 'id_categorie', referencedColumnName: 'id_categorie', nullable: false)]
    private ?Categorie $categorie = null;

    #[ORM\ManyToMany(targetEntity: Garage::class, mappedBy: 'prestations')]
    private Collection $garages;

    public function __construct()
    {
        $this->garages = new ArrayCollection();
    }


    public function getIdPrestation(): int
    {
        return $this->idPrestation;
    }

    public function getNomPrestation(): string
    {
        return $this->nomPrestation;
    }

    public function setNomPrestation(string $nomPrestation): static
    {
        $this->nomPrestation = $nomPrestation;
        return $this;
    }

    public function getDescriptionPrestation(): string
    {
        return $this->descriptionPrestation;
    }

    public function setDescriptionPrestation(string $descriptionPrestation): static
    {
        $this->descriptionPrestation = $descriptionPrestation;
        return $this;
    }

    public function getDureePrestation(): string
    {
        return $this->dureePrestation;
    }

    public function setDureePrestation(string $dureePrestation): static
    {
        $this->dureePrestation = $dureePrestation;
        return $this;
    }

    public function getCategorie(): ?Categorie
    {
        return $this->categorie;
    }

    public function setCategorie(?Categorie $categorie): static
    {
        $this->categorie = $categorie;
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
            $garage->addPrestation($this);
        }
        return $this;
    }

    public function removeGarage(Garage $garage): static
    {
        if ($this->garages->removeElement($garage)) {
            $garage->removePrestation($this);
        }
        return $this;
    }
}