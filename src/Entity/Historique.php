<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'historique')]
class Historique
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id_historique', type: 'integer', nullable: false)]
    private int $idHistorique;

    #[ORM\Column(name: 'date_intervention', type: 'date', nullable: false)]
    private \DateTimeInterface $dateIntervention;

    #[ORM\Column(name: 'compte_rendu', type: 'text', nullable: false)]
    private string $compteRendu;

    #[ORM\ManyToOne(targetEntity: RendezVous::class, inversedBy: 'historiques')]
    #[ORM\JoinColumn(name: 'id_rdv', referencedColumnName: 'id_rdv', nullable: false)]
    private ?RendezVous $rdv = null;

    public function getIdHistorique(): int
    {
        return $this->idHistorique;
    }

    public function getDateIntervention(): \DateTimeInterface
    {
        return $this->dateIntervention;
    }

    public function setDateIntervention(\DateTimeInterface $dateIntervention): static
    {
        $this->dateIntervention = $dateIntervention;

        return $this;
    }

    public function getCompteRendu(): string
    {
        return $this->compteRendu;
    }

    public function setCompteRendu(string $compteRendu): static
    {
        $this->compteRendu = $compteRendu;

        return $this;
    }

    public function getRdv(): ?RendezVous
    {
        return $this->rdv;
    }

    public function setRdv(?RendezVous $rdv): static
    {
        $this->rdv = $rdv;

        return $this;
    }

}
