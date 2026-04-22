<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'prestation')]
class Prestation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id_prestation', type: 'integer')]
    private ?int $idPrestation = null;

    #[ORM\Column(name: 'nom_prestation', type: 'string', length: 100)]
    private string $nomPrestation;

    #[ORM\Column(name: 'description_prestation', type: 'text')]
    private string $descriptionPrestation;

    #[ORM\Column(name: 'duree_prestation', type: 'string', length: 10)]
    private string $dureePrestation;

    #[ORM\ManyToOne(targetEntity: Categorie::class, inversedBy: 'prestations')]
    #[ORM\JoinColumn(name: 'id_categorie', referencedColumnName: 'id_categorie', nullable: false)]
    private ?Categorie $categorie = null;

    #[ORM\OneToMany(targetEntity: Proposer::class, mappedBy: 'prestation')]
    private Collection $proposers;


    public function __construct()
    {
        $this->proposers = new ArrayCollection();
    }


    public function getIdPrestation(): int
    {
        return $this->idPrestation;
    }

    public function getNomPrestation(): string
    {
        return $this->nomPrestation;
    }

    public function setNomPrestation(string $nomPrestation): static
    {
        $this->nomPrestation = $nomPrestation;
        return $this;
    }

    public function getDescriptionPrestation(): string
    {
        return $this->descriptionPrestation;
    }

    public function setDescriptionPrestation(string $descriptionPrestation): static
    {
        $this->descriptionPrestation = $descriptionPrestation;
        return $this;
    }

    public function getDureePrestation(): string
    {
        return $this->dureePrestation;
    }

    public function setDureePrestation(string $dureePrestation): static
    {
        $this->dureePrestation = $dureePrestation;
        return $this;
    }

    public function getCategorie(): ?Categorie
    {
        return $this->categorie;
    }

    public function setCategorie(?Categorie $categorie): static
    {
        $this->categorie = $categorie;
        return $this;
    }

    public function getProposers(): Collection
    {
        return $this->proposers;
    }

    public function addProposer(Proposer $proposer): static
    {
        if (!$this->proposers->contains($proposer)) {
            $this->proposers->add($proposer);
            $proposer->setPrestation($this);
        }
        return $this;
    }

    public function removeProposer(Proposer $proposer): static
    {
        if ($this->proposers->removeElement($proposer)) {
            $proposer->setPrestation(null);
        }
        return $this;
    }
}