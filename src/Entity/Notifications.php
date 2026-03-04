<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'notifications')]
class Notifications
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id_notifications', type: 'integer', nullable: false)]
    private int $idNotifications;

    #[ORM\Column(name: 'contenue', type: 'text', nullable: false)]
    private string $contenue;

    #[ORM\Column(name: 'date_envoi', type: 'datetime', nullable: false)]
    private \DateTimeInterface $dateEnvoi;

    #[ORM\ManyToOne(targetEntity: StatusNotif::class, inversedBy: 'notificationsList')]
    #[ORM\JoinColumn(name: 'id_status_notif', referencedColumnName: 'id_status_notif', nullable: false)]
    private ?StatusNotif $statusNotif = null;

    #[ORM\ManyToOne(targetEntity: RendezVous::class, inversedBy: 'notificationsList')]
    #[ORM\JoinColumn(name: 'id_rdv', referencedColumnName: 'id_rdv', nullable: false)]
    private ?RendezVous $rdv = null;

    public function getIdNotifications(): int
    {
        return $this->idNotifications;
    }

    public function getContenue(): string
    {
        return $this->contenue;
    }

    public function setContenue(string $contenue): static
    {
        $this->contenue = $contenue;

        return $this;
    }

    public function getDateEnvoi(): \DateTimeInterface
    {
        return $this->dateEnvoi;
    }

    public function setDateEnvoi(\DateTimeInterface $dateEnvoi): static
    {
        $this->dateEnvoi = $dateEnvoi;

        return $this;
    }

    public function getStatusNotif(): ?StatusNotif
    {
        return $this->statusNotif;
    }

    public function setStatusNotif(?StatusNotif $statusNotif): static
    {
        $this->statusNotif = $statusNotif;

        return $this;
    }

    public function getRdv(): ?RendezVous
    {
        return $this->rdv;
    }

    public function setRdv(?RendezVous $rdv): static
    {
        $this->rdv = $rdv;

        return $this;
    }

}
