<?php

namespace App\Entity;

use App\Entity\Garage;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'rendez_vous')]
class RendezVous
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id_rdv', type: 'integer', nullable: false)]
    private int $idRdv;

    #[ORM\Column(name: 'date_debut', type: 'datetime', nullable: false)]
    private \DateTimeInterface $dateDebut;

    #[ORM\Column(name: 'date_fin', type: 'datetime', nullable: false)]
    private \DateTimeInterface $dateFin;

    #[ORM\Column(name: 'motif_refus', type: 'text', nullable: false)]
    private string $motifRefus;

    #[ORM\Column(name: 'commantaire_client', type: 'text', nullable: false)]
    private string $commantaireClient;

    #[ORM\ManyToOne(targetEntity: Garage::class, inversedBy: 'rendezVousList')]
    #[ORM\JoinColumn(name: 'id_garage', referencedColumnName: 'id_garage', nullable: false)]
    private ?Garage $garage = null;

    #[ORM\ManyToOne(targetEntity: Vehicule::class, inversedBy: 'rendezVousList')]
    #[ORM\JoinColumn(name: 'id_vehicule', referencedColumnName: 'id_vehicule', nullable: false)]
    private ?Vehicule $vehicule = null;

    #[ORM\ManyToOne(targetEntity: StatusRdv::class, inversedBy: 'rendezVousList')]
    #[ORM\JoinColumn(name: 'id_status_rdv', referencedColumnName: 'id_status_rdv', nullable: false)]
    private ?StatusRdv $statusRdv = null;

    #[ORM\OneToMany(mappedBy: 'rdv', targetEntity: Lier::class)]
    private Collection $liers;

    #[ORM\OneToMany(mappedBy: 'rdv', targetEntity: Files::class)]
    private Collection $filesList;

    #[ORM\OneToMany(mappedBy: 'rdv', targetEntity: Notifications::class)]
    private Collection $notificationsList;

    #[ORM\OneToMany(mappedBy: 'rdv', targetEntity: Historique::class)]
    private Collection $historiques;

    public function __construct()
    {
        $this->liers = new ArrayCollection();
        $this->filesList = new ArrayCollection();
        $this->notificationsList = new ArrayCollection();
        $this->historiques = new ArrayCollection();
    }

    public function getIdRdv(): int
    {
        return $this->idRdv;
    }

    public function getDateDebut(): \DateTimeInterface
    {
        return $this->dateDebut;
    }

    public function setDateDebut(\DateTimeInterface $dateDebut): static
    {
        $this->dateDebut = $dateDebut;

        return $this;
    }

    public function getDateFin(): \DateTimeInterface
    {
        return $this->dateFin;
    }

    public function setDateFin(\DateTimeInterface $dateFin): static
    {
        $this->dateFin = $dateFin;

        return $this;
    }

    public function getMotifRefus(): string
    {
        return $this->motifRefus;
    }

    public function setMotifRefus(string $motifRefus): static
    {
        $this->motifRefus = $motifRefus;

        return $this;
    }

    public function getCommantaireClient(): string
    {
        return $this->commantaireClient;
    }

    public function setCommantaireClient(string $commantaireClient): static
    {
        $this->commantaireClient = $commantaireClient;

        return $this;
    }

    public function getGarage(): ?Garage
    {
        return $this->garage;
    }

    public function setGarage(?Garage $garage): static
    {
        $this->garage = $garage;

        return $this;
    }

    public function getVehicule(): ?Vehicule
    {
        return $this->vehicule;
    }

    public function setVehicule(?Vehicule $vehicule): static
    {
        $this->vehicule = $vehicule;

        return $this;
    }

    public function getStatusRdv(): ?StatusRdv
    {
        return $this->statusRdv;
    }

    public function setStatusRdv(?StatusRdv $statusRdv): static
    {
        $this->statusRdv = $statusRdv;

        return $this;
    }


    public function getLiers(): Collection
    {
        return $this->liers;
    }

    public function addLier(Lier $lier): static
    {
        if (!$this->liers->contains($lier)) {
            $this->liers->add($lier);
            $lier->setRdv($this);
        }

        return $this;
    }

    public function removeLier(Lier $lier): static
    {
        if ($this->liers->removeElement($lier)) {
            if ($lier->getRdv() === $this) {
                $lier->setRdv(null);
            }
        }

        return $this;
    }


    public function getFilesList(): Collection
    {
        return $this->filesList;
    }

    public function addFiles(Files $files): static
    {
        if (!$this->filesList->contains($files)) {
            $this->filesList->add($files);
            $files->setRdv($this);
        }

        return $this;
    }

    public function removeFiles(Files $files): static
    {
        if ($this->filesList->removeElement($files)) {
            if ($files->getRdv() === $this) {
                $files->setRdv(null);
            }
        }

        return $this;
    }


    public function getNotificationsList(): Collection
    {
        return $this->notificationsList;
    }

    public function addNotifications(Notifications $notifications): static
    {
        if (!$this->notificationsList->contains($notifications)) {
            $this->notificationsList->add($notifications);
            $notifications->setRdv($this);
        }

        return $this;
    }

    public function removeNotifications(Notifications $notifications): static
    {
        if ($this->notificationsList->removeElement($notifications)) {
            if ($notifications->getRdv() === $this) {
                $notifications->setRdv(null);
            }
        }

        return $this;
    }


    public function getHistoriques(): Collection
    {
        return $this->historiques;
    }

    public function addHistorique(Historique $historique): static
    {
        if (!$this->historiques->contains($historique)) {
            $this->historiques->add($historique);
            $historique->setRdv($this);
        }

        return $this;
    }

    public function removeHistorique(Historique $historique): static
    {
        if ($this->historiques->removeElement($historique)) {
            if ($historique->getRdv() === $this) {
                $historique->setRdv(null);
            }
        }

        return $this;
    }

}
