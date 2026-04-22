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
    // pour gerer la double reservation 
   // Vérifie si un créneau est déjà pris pour un garage
    /*public function existeCreneauPris(
        int $garageId,
        \DateTimeInterface $dateDebut,
        \DateTimeInterface $dateFin
    ): bool {
        $count = $this->createQueryBuilder('r')
            ->select('COUNT(r.idRdv)')
            ->andWhere('r.garage = :garage')
            ->andWhere('(r.dateDebut < :dateFin AND r.dateFin > :dateDebut)')
            ->setParameter('garage', $garageId)
            ->setParameter('dateDebut', $dateDebut)
            ->setParameter('dateFin', $dateFin)
            ->getQuery()
            ->getSingleScalarResult();

        return $count > 0;
    }*/
}
