<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'categorie')]
class Categorie
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id_categorie', type: 'integer', nullable: false)]
    private int $idCategorie;

    #[ORM\Column(name: 'nom_categorie', type: 'string', length: 50, nullable: false)]
    private string $nomCategorie;

    #[ORM\OneToMany(mappedBy: 'categorie', targetEntity: Prestation::class)]
    private Collection $prestations;

    public function __construct()
    {
        $this->prestations = new ArrayCollection();
    }

    public function getIdCategorie(): int
    {
        return $this->idCategorie;
    }

    public function getNomCategorie(): string
    {
        return $this->nomCategorie;
    }

    public function setNomCategorie(string $nomCategorie): static
    {
        $this->nomCategorie = $nomCategorie;

        return $this;
    }


    public function getPrestations(): Collection
    {
        return $this->prestations;
    }

    public function addPrestation(Prestation $prestation): static
    {
        if (!$this->prestations->contains($prestation)) {
            $this->prestations->add($prestation);
            $prestation->setCategorie($this);
        }

        return $this;
    }

    public function removePrestation(Prestation $prestation): static
    {
        if ($this->prestations->removeElement($prestation)) {
            if ($prestation->getCategorie() === $this) {
                $prestation->setCategorie(null);
            }
        }

        return $this;
    }

}
