<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'reseau_sociaux')]
class ReseauSociaux
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id_reseau', type: 'integer', nullable: false)]
    private int $idReseau;

    #[ORM\Column(name: 'nom_reseaux', type: 'string', length: 255, nullable: true)]
    private ?string $nomReseaux = null;

    #[ORM\OneToMany(mappedBy: 'reseau', targetEntity: Valeur::class)]
    private Collection $valeurs;

    public function __construct()
    {
        $this->valeurs = new ArrayCollection();
    }

    public function getIdReseau(): int
    {
        return $this->idReseau;
    }

    public function getNomReseaux(): ?string
    {
        return $this->nomReseaux;
    }

    public function setNomReseaux(?string $nomReseaux): static
    {
        $this->nomReseaux = $nomReseaux;

        return $this;
    }


    public function getValeurs(): Collection
    {
        return $this->valeurs;
    }

    public function addValeur(Valeur $valeur): static
    {
        if (!$this->valeurs->contains($valeur)) {
            $this->valeurs->add($valeur);
            $valeur->setReseau($this);
        }

        return $this;
    }

    public function removeValeur(Valeur $valeur): static
    {
        if ($this->valeurs->removeElement($valeur)) {
            if ($valeur->getReseau() === $this) {
                $valeur->setReseau(null);
            }
        }

        return $this;
    }

}
