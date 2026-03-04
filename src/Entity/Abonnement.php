<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'abonnement')]
class Abonnement
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id_abonnement', type: 'integer', nullable: false)]
    private int $idAbonnement;

    #[ORM\Column(name: 'lib_abonnement', type: 'string', length: 255, nullable: false)]
    private string $libAbonnement;

    #[ORM\Column(name: 'tarif_abonnement', type: 'decimal', precision: 19, scale: 4, nullable: false)]
    private string $tarifAbonnement;

    #[ORM\OneToMany(mappedBy: 'abonnement', targetEntity: Souscription::class)]
    private Collection $souscriptions;

    public function __construct()
    {
        $this->souscriptions = new ArrayCollection();
    }

    public function getIdAbonnement(): int
    {
        return $this->idAbonnement;
    }

    public function getLibAbonnement(): string
    {
        return $this->libAbonnement;
    }

    public function setLibAbonnement(string $libAbonnement): static
    {
        $this->libAbonnement = $libAbonnement;

        return $this;
    }

    public function getTarifAbonnement(): string
    {
        return $this->tarifAbonnement;
    }

    public function setTarifAbonnement(string $tarifAbonnement): static
    {
        $this->tarifAbonnement = $tarifAbonnement;

        return $this;
    }

/** @return Collection<int, Souscription> */
    public function getSouscriptions(): Collection
    {
        return $this->souscriptions;
    }

    public function addSouscription(Souscription $souscription): static
    {
        if (!$this->souscriptions->contains($souscription)) {
            $this->souscriptions->add($souscription);
            $souscription->setAbonnement($this);
        }

        return $this;
    }

    public function removeSouscription(Souscription $souscription): static
    {
        if ($this->souscriptions->removeElement($souscription)) {
            if ($souscription->getAbonnement() === $this) {
                $souscription->setAbonnement(null);
            }
        }

        return $this;
    }

}
