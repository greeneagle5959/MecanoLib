<?php

namespace App\Entity;

use App\Entity\Utilisateur;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'garage')]
class Garage
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id_garage', type: 'integer')]
    private ?int $idGarage = null;

    #[ORM\Column(name: 'nom_garage', type: 'string', length: 50)]
    private string $nomGarage;

    #[ORM\Column(name: 'email_garage', type: 'string', length: 255)]
    private string $emailGarage;

    #[ORM\Column(name: 'telephone_garage', type: 'string', length: 15)]
    private string $telephoneGarage;

    #[ORM\Column(name: 'adresse_garage', type: 'string', length: 80)]
    private string $adresseGarage;

    #[ORM\Column(name: 'date_creation', type: 'datetime')]
    private \DateTimeInterface $dateCreation;

    #[ORM\Column(name: 'siret', type: 'string', length: 15)]
    private string $siret;

    #[ORM\Column(name: 'tva', type: 'string', length: 25)]
    private string $tva;

    #[ORM\Column(name: 'img_garage', type: 'string', length: 255, nullable: true)]
    private ?string $imgGarage = null;

    #[ORM\Column(name: 'img_logo', type: 'string', length: 255, nullable: true)]
    private ?string $imgLogo = null;

    #[ORM\ManyToOne(targetEntity: Ville::class, inversedBy: 'garages')]
    #[ORM\JoinColumn(name: 'id_ville', referencedColumnName: 'id_ville', nullable: false)]
    private ?Ville $ville = null;

    #[ORM\Column(name: 'is_valide', type: 'boolean', options: ['default' => false])]
    private bool $isValide = false;

    #[ORM\ManyToOne(targetEntity: Utilisateur::class, inversedBy: 'garages')]
    #[ORM\JoinColumn(name: "id_utilisateur", referencedColumnName: "id_utilisateur", onDelete: "CASCADE")]
    private ?Utilisateur $utilisateur = null;

    #[ORM\OneToMany(mappedBy: 'garage', targetEntity: RendezVous::class)]
    private Collection $rendezVousList;

    #[ORM\OneToMany(mappedBy: 'garage', targetEntity: Avis::class)]
    private Collection $avisList;

    #[ORM\OneToMany(mappedBy: 'garage', targetEntity: Proposer::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $proposers;

    #[ORM\OneToMany(mappedBy: 'garage', targetEntity: Souscription::class)]
    private Collection $souscriptions;

    #[ORM\OneToMany(mappedBy: 'garage', targetEntity: Valeur::class)]
    private Collection $valeurs;

    public function __construct()
    {
        $this->rendezVousList = new ArrayCollection();
        $this->avisList = new ArrayCollection();
        $this->proposers = new ArrayCollection();
        $this->souscriptions = new ArrayCollection();
        $this->valeurs = new ArrayCollection();
    }

    // Getters & Setters

    public function getIdGarage(): int
    {
        return $this->idGarage;
    }

    public function getNomGarage(): string
    {
        return $this->nomGarage;
    }

    public function setNomGarage(string $nomGarage): static
    {
        $this->nomGarage = $nomGarage;
        return $this;
    }

    public function getEmailGarage(): string
    {
        return $this->emailGarage;
    }

    public function setEmailGarage(string $emailGarage): static
    {
        $this->emailGarage = $emailGarage;
        return $this;
    }

    public function getTelephoneGarage(): string
    {
        return $this->telephoneGarage;
    }

    public function setTelephoneGarage(string $telephoneGarage): static
    {
        $this->telephoneGarage = $telephoneGarage;
        return $this;
    }

    public function getAdresseGarage(): string
    {
        return $this->adresseGarage;
    }

    public function setAdresseGarage(string $adresseGarage): static
    {
        $this->adresseGarage = $adresseGarage;
        return $this;
    }

    public function getDateCreation(): \DateTimeInterface
    {
        return $this->dateCreation;
    }

    public function setDateCreation(\DateTimeInterface $dateCreation): static
    {
        $this->dateCreation = $dateCreation;
        return $this;
    }

    public function getSiret(): string
    {
        return $this->siret;
    }

    public function setSiret(string $siret): static
    {
        $this->siret = $siret;
        return $this;
    }

    public function getTva(): string
    {
        return $this->tva;
    }

    public function setTva(string $tva): static
    {
        $this->tva = $tva;
        return $this;
    }

    public function getImgGarage(): ?string
    {
        return $this->imgGarage;
    }

    public function setImgGarage(?string $imgGarage): static
    {
        $this->imgGarage = $imgGarage;
        return $this;
    }

    public function getImgLogo(): ?string
    {
        return $this->imgLogo;
    }

    public function setImgLogo(?string $imgLogo): static
    {
        $this->imgLogo = $imgLogo;
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

    public function getVille(): ?Ville
    {
        return $this->ville;
    }

    public function setVille(?Ville $ville): static
    {
        $this->ville = $ville;
        return $this;
    }

    public function getIsValide(): bool
    {
        return $this->isValide;
    }

    public function setIsValide(bool $isValide): static
    {
        $this->isValide = $isValide;
        return $this;
    }

    public function getPrestations(): Collection
    {
        $prestations = new ArrayCollection();
        foreach ($this->proposers as $proposer) {
            $prestation = $proposer->getPrestation();
            if (!$prestations->contains($prestation)) {
                $prestations->add($prestation);
            }
        }

        return $prestations;
    }

    public function addPrestation(Prestation $prestation): static
    {
        foreach ($this->proposers as $proposer) {
            if ($proposer->getPrestation() === $prestation) {
                return $this;
            }
        }

        $proposer = new Proposer();
        $proposer->setGarage($this);
        $proposer->setPrestation($prestation);
        $this->proposers->add($proposer);
        $prestation->addProposer($proposer);

        return $this;
    }

    public function removePrestation(Prestation $prestation): static
    {
        foreach ($this->proposers as $proposer) {
            if ($proposer->getPrestation() === $prestation) {
                $this->proposers->removeElement($proposer);
                $prestation->removeProposer($proposer);
            }
        }

        return $this;
    }

    public function getAvisList(): Collection
    {
        return $this->avisList;
    }

    public function getProposers(): Collection
    {
        return $this->proposers;
    }

    public function getSouscriptions(): Collection
    {
        return $this->souscriptions;
    }

    public function getValeurs(): Collection
    {
        return $this->valeurs;
    }

    public function getRendezVousList(): Collection
    {
        return $this->rendezVousList;
    }

    public function addRendezVous(RendezVous $rendezVous): static
    {
        if (!$this->rendezVousList->contains($rendezVous)) {
            $this->rendezVousList->add($rendezVous);
            $rendezVous->setGarage($this);
        }

        return $this;
    }

    public function removeRendezVous(RendezVous $rendezVous): static
    {
        if ($this->rendezVousList->removeElement($rendezVous)) {
            if ($rendezVous->getGarage() === $this) {
                $rendezVous->setGarage(null);
            }
        }

        return $this;
    }
}
