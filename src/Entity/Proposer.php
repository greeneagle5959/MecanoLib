<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'proposer')]
class Proposer
{
    #[ORM\Id]
    #[ORM\ManyToOne(targetEntity: Garage::class)]
    #[ORM\JoinColumn(name: 'id_garage', referencedColumnName: 'id_garage', nullable: false)]
    private Garage $garage;

    #[ORM\Id]
    #[ORM\ManyToOne(targetEntity: Prestation::class)]
    #[ORM\JoinColumn(name: 'id_prestation', referencedColumnName: 'id_prestation', nullable: false)]
    private Prestation $prestation;

    // Exemple : ajouter un champ supplémentaire
    #[ORM\Column(type: 'decimal', precision: 8, scale: 2, nullable: true)]
    private ?float $prix = null;

    public function getGarage(): Garage
    {
        return $this->garage;
    }

    public function setGarage(Garage $garage): static
    {
        $this->garage = $garage;
        return $this;
    }

    public function getPrestation(): Prestation
    {
        return $this->prestation;
    }

    public function setPrestation(Prestation $prestation): static
    {
        $this->prestation = $prestation;
        return $this;
    }

    public function getPrix(): ?float
    {
        return $this->prix;
    }

    public function setPrix(?float $prix): static
    {
        $this->prix = $prix;
        return $this;
    }
}