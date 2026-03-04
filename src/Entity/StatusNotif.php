<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'status_notif')]
class StatusNotif
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id_status_notif', type: 'integer', nullable: false)]
    private int $idStatusNotif;

    #[ORM\Column(name: 'lib_status_notif', type: 'string', length: 50, nullable: false)]
    private string $libStatusNotif;

    #[ORM\OneToMany(mappedBy: 'statusNotif', targetEntity: Notifications::class)]
    private Collection $notificationsList;

    public function __construct()
    {
        $this->notificationsList = new ArrayCollection();
    }

    public function getIdStatusNotif(): int
    {
        return $this->idStatusNotif;
    }

    public function getLibStatusNotif(): string
    {
        return $this->libStatusNotif;
    }

    public function setLibStatusNotif(string $libStatusNotif): static
    {
        $this->libStatusNotif = $libStatusNotif;

        return $this;
    }

/** @return Collection<int, Notifications> */
    public function getNotificationsList(): Collection
    {
        return $this->notificationsList;
    }

    public function addNotifications(Notifications $notifications): static
    {
        if (!$this->notificationsList->contains($notifications)) {
            $this->notificationsList->add($notifications);
            $notifications->setStatusNotif($this);
        }

        return $this;
    }

    public function removeNotifications(Notifications $notifications): static
    {
        if ($this->notificationsList->removeElement($notifications)) {
            if ($notifications->getStatusNotif() === $this) {
                $notifications->setStatusNotif(null);
            }
        }

        return $this;
    }

}
