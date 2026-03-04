<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'files')]
class Files
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id_files', type: 'integer', nullable: false)]
    private int $idFiles;

    #[ORM\Column(name: 'img', type: 'string', length: 255, nullable: false)]
    private string $img;

    #[ORM\Column(name: 'commentaire', type: 'text', nullable: false)]
    private string $commentaire;

    #[ORM\ManyToOne(targetEntity: RendezVous::class, inversedBy: 'filesList')]
    #[ORM\JoinColumn(name: 'id_rdv', referencedColumnName: 'id_rdv', nullable: false)]
    private ?RendezVous $rdv = null;

    public function getIdFiles(): int
    {
        return $this->idFiles;
    }

    public function getImg(): string
    {
        return $this->img;
    }

    public function setImg(string $img): static
    {
        $this->img = $img;

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
