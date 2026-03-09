<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\Entity\RendezVous;
use App\Entity\Prestation;

#[ORM\Entity]
#[ORM\Table(name: 'lier')]
class Lier
{
    #[ORM\Id]
    #[ORM\ManyToOne(targetEntity: Prestation::class)]
    #[ORM\JoinColumn(name: 'id_prestation', referencedColumnName: 'id_prestation', nullable: false)]
    private Prestation $prestation;

    #[ORM\Id]
    #[ORM\ManyToOne(targetEntity: RendezVous::class, inversedBy: 'liers')]
    #[ORM\JoinColumn(name: 'id_rdv', referencedColumnName: 'id_rdv', nullable: false)]
    private RendezVous $rdv;

    public function getPrestation(): Prestation
    {
        return $this->prestation;
    }

    public function setPrestation(Prestation $prestation): static
    {
        $this->prestation = $prestation;
        return $this;
    }

    public function getRdv(): RendezVous
    {
        return $this->rdv;
    }

    public function setRdv(RendezVous $rdv): static
    {
        $this->rdv = $rdv;
        return $this;
    }
}