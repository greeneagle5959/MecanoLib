<?php

namespace App\Repository;

use App\Entity\RendezVous;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<RendezVous>
 */
class RendezVousRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, RendezVous::class);
    }
   public function findRdvByClient(int $clientId): array
    {
        return $this->createQueryBuilder('r')
            ->join('r.garage', 'g')
            ->join('r.vehicule', 'v')
            ->join('r.liers', 'l')
            ->join('l.prestation', 'p')
            ->join('v.client', 'c')
            ->where('c.idClient = :clientId')
            ->setParameter('clientId', $clientId)
            ->select('
                r.idRdv AS id_rdv,
                r.dateDebut AS date_debut,
                r.dateFin AS date_fin,
                g.nomGarage AS garage,
                v.imatriculationVehicule AS immatriculation,
                p.nomPrestation AS prestation
            ')
            ->orderBy('r.dateDebut', 'DESC')
            ->getQuery()
            ->getArrayResult(); 
    }
}
