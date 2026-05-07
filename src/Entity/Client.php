<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity]
#[ORM\Table(name: 'client')]
class Client
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id_client', type: 'integer', nullable: false)]
    private int $idClient;


   #[Assert\NotBlank(message: "Le nom est obligatoire")]
    #[Assert\Length(min: 2, max: 50)]
    #[Assert\Regex(
        pattern: "/^[a-zA-ZÀ-ÿ\s-]+$/u",
        message: "Nom invalide"
    )]
    #[ORM\Column(name: 'nom_client', type: 'string', length: 30, nullable: false)]
    private string $nomClient;


    #[Assert\NotBlank(message: "Le prénom est obligatoire")]
    #[Assert\Length(min: 2, max: 50)]
    #[Assert\Regex(
        pattern: "/^[a-zA-ZÀ-ÿ\s-]+$/u",
        message: "Prénom invalide"
    )]
    #[ORM\Column(name: 'prenom_client', type: 'string', length: 30, nullable: false)]
    private string $prenomClient;


    #[Assert\NotBlank(message: "Téléphone obligatoire")]
    #[Assert\Regex(
        pattern: "/^[0-9+\s]{8,20}$/",
        message: "Téléphone invalide"
    )]
    #[ORM\Column(name: 'telephone_client', type: 'string', length: 15, nullable: false)]
    private string $telephoneClient;

    #[ORM\Column(name: 'consentement_client', type: 'boolean', nullable: false)]
    private bool $consentementClient;

    #[ORM\Column(name: 'date_inscription', type: 'datetime', nullable: false)]
    private \DateTimeInterface $dateInscription;

    #[ORM\ManyToOne(targetEntity: Utilisateur::class, inversedBy: 'clients')]
    #[ORM\JoinColumn(name: 'id_utilisateur', referencedColumnName: 'id_utilisateur', nullable: false)]
    private ?Utilisateur $utilisateur = null;

    #[ORM\OneToMany(mappedBy: 'client', targetEntity: Vehicule::class)]
    private Collection $vehicules;

    #[ORM\OneToMany(mappedBy: 'client', targetEntity: Avis::class)]
    private Collection $avisList;

    public function __construct()
    {
        $this->vehicules = new ArrayCollection();
        $this->avisList = new ArrayCollection();
    }

    public function getIdClient(): int
    {
        return $this->idClient;
    }

    public function getNomClient(): string
    {
        return $this->nomClient;
    }

    public function setNomClient(string $nomClient): static
    {
        $this->nomClient = $nomClient;

        return $this;
    }

    public function getPrenomClient(): string
    {
        return $this->prenomClient;
    }

    public function setPrenomClient(string $prenomClient): static
    {
        $this->prenomClient = $prenomClient;

        return $this;
    }

    public function getTelephoneClient(): string
    {
        return $this->telephoneClient;
    }

    public function setTelephoneClient(string $telephoneClient): static
    {
        $this->telephoneClient = $telephoneClient;

        return $this;
    }

    public function getConsentementClient(): bool
    {
        return $this->consentementClient;
    }

    public function setConsentementClient(bool $consentementClient): static
    {
        $this->consentementClient = $consentementClient;

        return $this;
    }

    public function getDateInscription(): \DateTimeInterface
    {
        return $this->dateInscription;
    }

    public function setDateInscription(\DateTimeInterface $dateInscription): static
    {
        $this->dateInscription = $dateInscription;

        return $this;
    }

    public function getUtilisateur(): ?Utilisateur
    {
        return $this->utilisateur;
    }

    public function setUtilisateur(?Utilisateur $utilisateur): static
    {
        $this->utilisateur = $utilisateur;

        return $this;
    }

/** @return Collection<int, Vehicule> */
    public function getVehicules(): Collection
    {
        return $this->vehicules;
    }

    public function addVehicule(Vehicule $vehicule): static
    {
        if (!$this->vehicules->contains($vehicule)) {
            $this->vehicules->add($vehicule);
            $vehicule->setClient($this);
        }

        return $this;
    }

    public function removeVehicule(Vehicule $vehicule): static
    {
        if ($this->vehicules->removeElement($vehicule)) {
            if ($vehicule->getClient() === $this) {
                $vehicule->setClient(null);
            }
        }

        return $this;
    }

/** @return Collection<int, Avis> */
    public function getAvisList(): Collection
    {
        return $this->avisList;
    }

    public function addAvis(Avis $avis): static
    {
        if (!$this->avisList->contains($avis)) {
            $this->avisList->add($avis);
            $avis->setClient($this);
        }

        return $this;
    }

    public function removeAvis(Avis $avis): static
    {
        if ($this->avisList->removeElement($avis)) {
            if ($avis->getClient() === $this) {
                $avis->setClient(null);
            }
        }

        return $this;
    }

}
