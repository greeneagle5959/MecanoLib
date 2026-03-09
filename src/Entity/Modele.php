<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'modele')]
class Modele
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id_modele', type: 'integer')]
    private ?int $idModele = null;

    #[ORM\Column(name: 'nom_modele', type: 'string', length: 50)]
    private string $nomModele;

    #[ORM\ManyToOne(targetEntity: Marque::class, inversedBy: 'modeles')]
    #[ORM\JoinColumn(name: 'id_marque', referencedColumnName: 'id_marque', nullable: false)]
    private ?Marque $marque = null;

    public function getIdModele(): ?int
    {
        return $this->idModele;
    }

    public function getNomModele(): string
    {
        return $this->nomModele;
    }

    public function setNomModele(string $nomModele): self
    {
        $this->nomModele = $nomModele;
        return $this;
    }

    public function getMarque(): ?Marque
    {
        return $this->marque;
    }

    public function setMarque(?Marque $marque): self
    {
        $this->marque = $marque;
        return $this;
    }
}