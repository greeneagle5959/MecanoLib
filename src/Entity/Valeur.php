<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'valeur')]
class Valeur
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id_valeur', type: 'integer', nullable: false)]
    private int $idValeur;

    #[ORM\Column(name: 'lib_valeur', type: 'string', length: 255, nullable: false)]
    private string $libValeur;

    #[ORM\Column(name: 'qrcode', type: 'string', length: 255, nullable: true)]
    private ?string $qrcode = null;

    #[ORM\ManyToOne(targetEntity: Garage::class, inversedBy: 'valeurs')]
    #[ORM\JoinColumn(name: 'id_garage', referencedColumnName: 'id_garage', nullable: false)]
    private ?Garage $garage = null;

    #[ORM\ManyToOne(targetEntity: ReseauSociaux::class, inversedBy: 'valeurs')]
    #[ORM\JoinColumn(name: 'id_reseau', referencedColumnName: 'id_reseau', nullable: false)]
    private ?ReseauSociaux $reseau = null;

    public function getIdValeur(): int
    {
        return $this->idValeur;
    }

    public function getLibValeur(): string
    {
        return $this->libValeur;
    }

    public function setLibValeur(string $libValeur): static
    {
        $this->libValeur = $libValeur;

        return $this;
    }

    public function getQrcode(): ?string
    {
        return $this->qrcode;
    }

    public function setQrcode(?string $qrcode): static
    {
        $this->qrcode = $qrcode;

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

    public function getReseau(): ?ReseauSociaux
    {
        return $this->reseau;
    }

    public function setReseau(?ReseauSociaux $reseau): static
    {
        $this->reseau = $reseau;

        return $this;
    }

}
