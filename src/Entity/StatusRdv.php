<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'status_rdv')]
class StatusRdv
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id_status_rdv', type: 'integer', nullable: false)]
    private int $idStatusRdv;

    #[ORM\Column(name: 'lib_status_rdv', type: 'string', length: 50, nullable: false)]
    private string $libStatusRdv;

    #[ORM\OneToMany(mappedBy: 'statusRdv', targetEntity: RendezVous::class)]
    private Collection $rendezVousList;

    public function __construct()
    {
        $this->rendezVousList = new ArrayCollection();
    }

    public function getIdStatusRdv(): int
    {
        return $this->idStatusRdv;
    }

    public function getLibStatusRdv(): string
    {
        return $this->libStatusRdv;
    }

    public function setLibStatusRdv(string $libStatusRdv): static
    {
        $this->libStatusRdv = $libStatusRdv;

        return $this;
    }

/** @return Collection<int, RendezVous> */
    public function getRendezVousList(): Collection
    {
        return $this->rendezVousList;
    }

    public function addRendezVous(RendezVous $rendezVous): static
    {
        if (!$this->rendezVousList->contains($rendezVous)) {
            $this->rendezVousList->add($rendezVous);
            $rendezVous->setStatusRdv($this);
        }

        return $this;
    }

    public function removeRendezVous(RendezVous $rendezVous): static
    {
        if ($this->rendezVousList->removeElement($rendezVous)) {
            if ($rendezVous->getStatusRdv() === $this) {
                $rendezVous->setStatusRdv(null);
            }
        }

        return $this;
    }

}
