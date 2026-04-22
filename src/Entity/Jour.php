<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'jour')]
class Jour
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id_jour', type: 'integer')]
    private ?int $idJour = null;

    // LUNDI, MARDI, etc (table de référence)
    #[ORM\Column(name: 'lib_jour', type: 'string', length: 15)]
    private string $libJour;

    // Liaison avec les horaires via Associer
    #[ORM\OneToMany(mappedBy: 'jour', targetEntity: Associer::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $associers;

    public function __construct()
    {
        $this->associers = new ArrayCollection();
    }

    public function getIdJour(): ?int
    {
        return $this->idJour;
    }

    public function getLibJour(): string
    {
        return $this->libJour;
    }

    public function setLibJour(string $libJour): static
    {
        $this->libJour = strtoupper($libJour);
        return $this;
    }

    /** @return Collection<int, Associer> */
    public function getAssociers(): Collection
    {
        return $this->associers;
    }

    public function addAssocier(Associer $associer): static
    {
        if (!$this->associers->contains($associer)) {
            $this->associers->add($associer);
            $associer->setJour($this);
        }
        return $this;
    }

    public function removeAssocier(Associer $associer): static
    {
        if ($this->associers->removeElement($associer)) {
            if ($associer->getJour() === $this) {
                $associer->setJour(null);
            }
        }
        return $this;
    }
}
