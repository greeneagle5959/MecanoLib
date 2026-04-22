<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'associer')]
class Associer
{
    #[ORM\Id]
    #[ORM\ManyToOne(targetEntity: Jour::class, inversedBy: 'associers')]
    #[ORM\JoinColumn(name: 'id_jour', referencedColumnName: 'id_jour', nullable: false)]
    private ?Jour $jour = null;

    #[ORM\Id]
    #[ORM\ManyToOne(targetEntity: Garage::class)]
    #[ORM\JoinColumn(name: 'id_garage', referencedColumnName: 'id_garage', nullable: false)]
    private ?Garage $garage = null;

    #[ORM\ManyToOne(targetEntity: Horaire::class, inversedBy: 'associers')]
    #[ORM\JoinColumn(name: 'id_horaire', referencedColumnName: 'id_horaire', nullable: false)]
    private ?Horaire $horaire = null;

    public function getJour(): ?Jour
    {
        return $this->jour;
    }

    public function setJour(?Jour $jour): static
    {
        $this->jour = $jour;
        return $this;
    }

    public function getHoraire(): ?Horaire
    {
        return $this->horaire;
    }

    public function setHoraire(?Horaire $horaire): static
    {
        $this->horaire = $horaire;
        return $this;
    }

    public function getGarage(): ?Garage
    {
        return $this->garage;
    }

    public function setGarage(?Garage $garage): static
    {
        $this->garage = $garage;

        return $this;
    }
}
