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

    #[ORM\ManyToOne(targetEntity: Marque::class, inversedBy: 'modeles')]
    #[ORM\JoinColumn(name: 'id_marque', referencedColumnName: 'id_marque', nullable: false)]
    private ?Marque $marque = null;

    public function __construct()
    {
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

    public function getMarque(): ?Marque
    {
        return $this->marque;
    }

    public function setMarque(?Marque $marque): static
    {
        $this->marque = $marque;

        return $this;
    }

/** @return Collection<int, Marque> */
    public function getMarques(): Collection
    {
        return new ArrayCollection($this->marque ? [$this->marque] : []);
    }

    public function addMarque(Marque $marque): static
    {
        return $this->setMarque($marque);
    }

    public function removeMarque(Marque $marque): static
    {
        if ($this->marque === $marque) {
            $this->marque = null;
        }

        return $this;
    }

}
