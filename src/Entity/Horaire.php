<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'horaire')]
class Horaire
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id_horaire', type: 'integer', nullable: false)]
    private ?int $idHoraire = null;

    #[ORM\Column(name: 'hre_ouvre_matin', type: 'time', nullable: false)]
    private \DateTimeInterface $hreOuvreMatin;

    #[ORM\Column(name: 'hre_ferme_matin', type: 'time', nullable: false)]
    private \DateTimeInterface $hreFermeMatin;

    #[ORM\Column(name: 'hre_ouvre_soir', type: 'time', nullable: false)]
    private \DateTimeInterface $hreOuvreSoir;

    #[ORM\Column(name: 'hre_ferme_soir', type: 'time', nullable: false)]
    private \DateTimeInterface $hreFermeSoir;

    #[ORM\OneToMany(mappedBy: 'horaire', targetEntity: Associer::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $associers;

    public function __construct()
    {
        $this->associers = new ArrayCollection();
    }

    public function getIdHoraire(): ?int
    {
        return $this->idHoraire;
    }

    public function getHreOuvreMatin(): \DateTimeInterface
    {
        return $this->hreOuvreMatin;
    }

    public function setHreOuvreMatin(\DateTimeInterface $hreOuvreMatin): static
    {
        $this->hreOuvreMatin = $hreOuvreMatin;

        return $this;
    }

    public function getHreFermeMatin(): \DateTimeInterface
    {
        return $this->hreFermeMatin;
    }

    public function setHreFermeMatin(\DateTimeInterface $hreFermeMatin): static
    {
        $this->hreFermeMatin = $hreFermeMatin;

        return $this;
    }

    public function getHreOuvreSoir(): \DateTimeInterface
    {
        return $this->hreOuvreSoir;
    }

    public function setHreOuvreSoir(\DateTimeInterface $hreOuvreSoir): static
    {
        $this->hreOuvreSoir = $hreOuvreSoir;

        return $this;
    }

    public function getHreFermeSoir(): \DateTimeInterface
    {
        return $this->hreFermeSoir;
    }

    public function setHreFermeSoir(\DateTimeInterface $hreFermeSoir): static
    {
        $this->hreFermeSoir = $hreFermeSoir;

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
            $associer->setHoraire($this);
        }

        return $this;
    }

    public function removeAssocier(Associer $associer): static
    {
        if ($this->associers->removeElement($associer)) {
            if ($associer->getHoraire() === $this) {
                $associer->setHoraire(null);
            }
        }

        return $this;
    }
}
