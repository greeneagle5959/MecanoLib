<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'modele')]
class Modele
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id_modele', type: 'integer', nullable: false)]
    private int $idModele;

    #[ORM\Column(name: 'nom_modele', type: 'string', length: 50, nullable: false)]
    private string $nomModele;

    #[ORM\OneToMany(mappedBy: 'modele', targetEntity: Marque::class)]
    private Collection $marques;

    public function __construct()
    {
        $this->marques = new ArrayCollection();
    }

    public function getIdModele(): int
    {
        return $this->idModele;
    }

    public function getNomModele(): string
    {
        return $this->nomModele;
    }

    public function setNomModele(string $nomModele): static
    {
        $this->nomModele = $nomModele;

        return $this;
    }

/** @return Collection<int, Marque> */
    public function getMarques(): Collection
    {
        return $this->marques;
    }

    public function addMarque(Marque $marque): static
    {
        if (!$this->marques->contains($marque)) {
            $this->marques->add($marque);
            $marque->setModele($this);
        }

        return $this;
    }

    public function removeMarque(Marque $marque): static
    {
        if ($this->marques->removeElement($marque)) {
            if ($marque->getModele() === $this) {
                $marque->setModele(null);
            }
        }

        return $this;
    }

}
