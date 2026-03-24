<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'garage')]
class Garage
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id_garage', type: 'integer', nullable: false)]
    private int $idGarage;

    #[ORM\Column(name: 'nom_garage', type: 'string', length: 20, nullable: false)]
    private string $nomGarage;

    #[ORM\Column(name: 'email_garage', type: 'string', length: 255, nullable: false)]
    private string $emailGarage;

    #[ORM\Column(name: 'telephone_garage', type: 'string', length: 15, nullable: false)]
    private string $telephoneGarage;

    #[ORM\Column(name: 'adresse_garage', type: 'string', length: 80, nullable: false)]
    private string $adresseGarage;

    #[ORM\Column(name: 'date_creation', type: 'datetime', nullable: false)]
    private \DateTimeInterface $dateCreation;

    #[ORM\Column(name: 'siret', type: 'string', length: 15, nullable: false)]
    private string $siret;

    #[ORM\Column(name: 'tva', type: 'string', length: 25, nullable: false)]
    private string $tva;

    #[ORM\Column(name: 'img_garage', type: 'string', length: 255, nullable: true)]
    private ?string $imgGarage = null;

    #[ORM\Column(name: 'img_logo', type: 'string', length: 255, nullable: true)]
    private ?string $imgLogo = null;

    #[ORM\Column(name: 'is_valide', type: 'boolean', nullable: false)]
    private bool $isValide = false;

    #[ORM\ManyToOne(targetEntity: Utilisateur::class, inversedBy: 'garages')]
    #[ORM\JoinColumn(name: 'id_utilisateur', referencedColumnName: 'id_utilisateur', nullable: false)]
    private ?Utilisateur $utilisateur = null;

    #[ORM\ManyToOne(targetEntity: Ville::class, inversedBy: 'garages')]
    #[ORM\JoinColumn(name: 'id_ville', referencedColumnName: 'id_ville', nullable: false)]
    private ?Ville $ville = null;

    #[ORM\OneToMany(mappedBy: 'garage', targetEntity: Horaire::class)]
    private Collection $horaires;

    #[ORM\OneToMany(mappedBy: 'garage', targetEntity: Souscription::class)]
    private Collection $souscriptions;

    #[ORM\OneToMany(mappedBy: 'garage', targetEntity: Valeur::class)]
    private Collection $valeurs;

    #[ORM\OneToMany(mappedBy: 'garage', targetEntity: Prestation::class)]
    private Collection $prestations;

    #[ORM\OneToMany(mappedBy: 'garage', targetEntity: Avis::class)]
    private Collection $avisList;

    #[ORM\OneToMany(mappedBy: 'garage', targetEntity: RendezVous::class)]
    private Collection $rendezVousList;

    public function __construct()
    {
        $this->horaires = new ArrayCollection();
        $this->souscriptions = new ArrayCollection();
        $this->valeurs = new ArrayCollection();
        $this->prestations = new ArrayCollection();
        $this->avisList = new ArrayCollection();
        $this->rendezVousList = new ArrayCollection();
    }

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

    public function getIsValide(): bool
    {
        return $this->isValide;
    }

    public function setIsValide(bool $isValide): static
    {
        $this->isValide = $isValide;

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

/** @return Collection<int, Horaire> */
    public function getHoraires(): Collection
    {
        return $this->horaires;
    }

    public function addHoraire(Horaire $horaire): static
    {
        if (!$this->horaires->contains($horaire)) {
            $this->horaires->add($horaire);
            $horaire->setGarage($this);
        }

        return $this;
    }

    public function removeHoraire(Horaire $horaire): static
    {
        if ($this->horaires->removeElement($horaire)) {
            if ($horaire->getGarage() === $this) {
                $horaire->setGarage(null);
            }
        }

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
            $souscription->setGarage($this);
        }

        return $this;
    }

    public function removeSouscription(Souscription $souscription): static
    {
        if ($this->souscriptions->removeElement($souscription)) {
            if ($souscription->getGarage() === $this) {
                $souscription->setGarage(null);
            }
        }

        return $this;
    }

/** @return Collection<int, Valeur> */
    public function getValeurs(): Collection
    {
        return $this->valeurs;
    }

    public function addValeur(Valeur $valeur): static
    {
        if (!$this->valeurs->contains($valeur)) {
            $this->valeurs->add($valeur);
            $valeur->setGarage($this);
        }

        return $this;
    }

    public function removeValeur(Valeur $valeur): static
    {
        if ($this->valeurs->removeElement($valeur)) {
            if ($valeur->getGarage() === $this) {
                $valeur->setGarage(null);
            }
        }

        return $this;
    }

/** @return Collection<int, Prestation> */
    public function getPrestations(): Collection
    {
        return $this->prestations;
    }

    public function addPrestation(Prestation $prestation): static
    {
        if (!$this->prestations->contains($prestation)) {
            $this->prestations->add($prestation);
            $prestation->setGarage($this);
        }

        return $this;
    }

    public function removePrestation(Prestation $prestation): static
    {
        if ($this->prestations->removeElement($prestation)) {
            if ($prestation->getGarage() === $this) {
                $prestation->setGarage(null);
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
            $avis->setGarage($this);
        }

        return $this;
    }

    public function removeAvis(Avis $avis): static
    {
        if ($this->avisList->removeElement($avis)) {
            if ($avis->getGarage() === $this) {
                $avis->setGarage(null);
            }
        }

        return $this;
    }

/** @return Collection<int, RendezVous> */
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
