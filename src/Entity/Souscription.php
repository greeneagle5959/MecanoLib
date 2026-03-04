<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'souscription')]
class Souscription
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id_souscription', type: 'integer', nullable: false)]
    private int $idSouscription;

    #[ORM\Column(name: 'date_debut', type: 'datetime', nullable: false)]
    private \DateTimeInterface $dateDebut;

    #[ORM\Column(name: 'date_fin', type: 'datetime', nullable: false)]
    private \DateTimeInterface $dateFin;

    #[ORM\Column(name: 'prix', type: 'decimal', precision: 19, scale: 4, nullable: false)]
    private string $prix;

    #[ORM\Column(name: 'status', type: 'boolean', nullable: false)]
    private bool $status;

    #[ORM\ManyToOne(targetEntity: Garage::class, inversedBy: 'souscriptions')]
    #[ORM\JoinColumn(name: 'id_garage', referencedColumnName: 'id_garage', nullable: false)]
    private ?Garage $garage = null;

    #[ORM\ManyToOne(targetEntity: Abonnement::class, inversedBy: 'souscriptions')]
    #[ORM\JoinColumn(name: 'id_abonnement', referencedColumnName: 'id_abonnement', nullable: false)]
    private ?Abonnement $abonnement = null;

    public function getIdSouscription(): int
    {
        return $this->idSouscription;
    }

    public function getDateDebut(): \DateTimeInterface
    {
        return $this->dateDebut;
    }

    public function setDateDebut(\DateTimeInterface $dateDebut): static
    {
        $this->dateDebut = $dateDebut;

        return $this;
    }

    public function getDateFin(): \DateTimeInterface
    {
        return $this->dateFin;
    }

    public function setDateFin(\DateTimeInterface $dateFin): static
    {
        $this->dateFin = $dateFin;

        return $this;
    }

    public function getPrix(): string
    {
        return $this->prix;
    }

    public function setPrix(string $prix): static
    {
        $this->prix = $prix;

        return $this;
    }

    public function getStatus(): bool
    {
        return $this->status;
    }

    public function setStatus(bool $status): static
    {
        $this->status = $status;

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

    public function getAbonnement(): ?Abonnement
    {
        return $this->abonnement;
    }

    public function setAbonnement(?Abonnement $abonnement): static
    {
        $this->abonnement = $abonnement;

        return $this;
    }

}
