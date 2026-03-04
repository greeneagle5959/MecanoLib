<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'avis')]
class Avis
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id_avis', type: 'integer', nullable: false)]
    private int $idAvis;

    #[ORM\Column(name: 'note', type: 'integer', nullable: false)]
    private int $note;

    #[ORM\Column(name: 'commentaire', type: 'text', nullable: false)]
    private string $commentaire;

    #[ORM\Column(name: 'date_publication', type: 'datetime', nullable: false)]
    private \DateTimeInterface $datePublication;

    #[ORM\ManyToOne(targetEntity: Garage::class, inversedBy: 'avisList')]
    #[ORM\JoinColumn(name: 'id_garage', referencedColumnName: 'id_garage', nullable: false)]
    private ?Garage $garage = null;

    #[ORM\ManyToOne(targetEntity: Client::class, inversedBy: 'avisList')]
    #[ORM\JoinColumn(name: 'id_client', referencedColumnName: 'id_client', nullable: false)]
    private ?Client $client = null;

    public function getIdAvis(): int
    {
        return $this->idAvis;
    }

    public function getNote(): int
    {
        return $this->note;
    }

    public function setNote(int $note): static
    {
        $this->note = $note;

        return $this;
    }

    public function getCommentaire(): string
    {
        return $this->commentaire;
    }

    public function setCommentaire(string $commentaire): static
    {
        $this->commentaire = $commentaire;

        return $this;
    }

    public function getDatePublication(): \DateTimeInterface
    {
        return $this->datePublication;
    }

    public function setDatePublication(\DateTimeInterface $datePublication): static
    {
        $this->datePublication = $datePublication;

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

    public function getClient(): ?Client
    {
        return $this->client;
    }

    public function setClient(?Client $client): static
    {
        $this->client = $client;

        return $this;
    }

}
