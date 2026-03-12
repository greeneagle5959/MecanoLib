<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'vehicule')]
class Vehicule
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id_vehicule', type: 'integer', nullable: false)]
    private int $idVehicule;

    #[ORM\Column(name: 'imatriculation_vehicule', type: 'string', length: 10, nullable: false)]
    private string $imatriculationVehicule;

    #[ORM\Column(name: 'annee_vehicule', type: 'string', length: 4, nullable: false)]
    private string $anneeVehicule;

    #[ORM\ManyToOne(targetEntity: Client::class, inversedBy: 'vehicules')]
    #[ORM\JoinColumn(name: 'id_client', referencedColumnName: 'id_client', nullable: false)]
    private ?Client $client = null;

    #[ORM\ManyToOne(targetEntity: Marque::class, inversedBy: 'vehicules')]
    #[ORM\JoinColumn(name: 'id_marque', referencedColumnName: 'id_marque', nullable: false)]
    private ?Marque $marque = null;

    #[ORM\ManyToOne(targetEntity: Modele::class)]
    #[ORM\JoinColumn(name: 'id_modele', referencedColumnName: 'id_modele', nullable: false)]
    private ?Modele $modele = null;

    #[ORM\OneToMany(mappedBy: 'vehicule', targetEntity: RendezVous::class)]
    private Collection $rendezVousList;

    public function __construct()
    {
        $this->rendezVousList = new ArrayCollection();
    }

    public function getIdVehicule(): int
    {
        return $this->idVehicule;
    }

    public function getImatriculationVehicule(): string
    {
        return $this->imatriculationVehicule;
    }

    public function setImatriculationVehicule(string $imatriculationVehicule): static
    {
        $this->imatriculationVehicule = $imatriculationVehicule;

        return $this;
    }

    public function getAnneeVehicule(): string
    {
        return $this->anneeVehicule;
    }

    public function setAnneeVehicule(string $anneeVehicule): static
    {
        $this->anneeVehicule = $anneeVehicule;

        return $this;
    }

    public function getClient(): ?Client
    {
        return $this->client;
    }

    public function setClient(?Client $client): static
    {
        $this->client = $client;

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

    public function getModele(): ?Modele
    {
        return $this->modele;
    }

    public function setModele(?Modele $modele): self
    {
        $this->modele = $modele;
        return $this;
    }

    public function getRendezVousList(): Collection
    {
        return $this->rendezVousList;
    }

    public function addRendezVous(RendezVous $rendezVous): static
    {
        if (!$this->rendezVousList->contains($rendezVous)) {
            $this->rendezVousList->add($rendezVous);
            $rendezVous->setVehicule($this);
        }

        return $this;
    }

    public function removeRendezVous(RendezVous $rendezVous): static
    {
        if ($this->rendezVousList->removeElement($rendezVous)) {
            if ($rendezVous->getVehicule() === $this) {
                $rendezVous->setVehicule(null);
            }
        }

        return $this;
    }

}
