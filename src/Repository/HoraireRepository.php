<?php

namespace App\Repository;

use App\Entity\Horaire;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Horaire>
 */
class HoraireRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Horaire::class);
    }
   

    public function findExistingHoraire(
        $matinDebut,
        $matinFin,
        $soirDebut,
        $soirFin
    ): ?Horaire {
        return $this->createQueryBuilder('h')
            ->where('h.hreOuvreMatin = :om')
            ->andWhere('h.hreFermeMatin = :fm')
            ->andWhere('h.hreOuvreSoir = :os')
            ->andWhere('h.hreFermeSoir = :fs')
            ->setParameters([
                'om' => $matinDebut,
                'fm' => $matinFin,
                'os' => $soirDebut,
                'fs' => $soirFin,
            ])
            ->getQuery()
            ->getOneOrNullResult();
    }
}
