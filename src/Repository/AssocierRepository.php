<?php

namespace App\Repository;

use App\Entity\Associer;
use App\Entity\Horaire;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Associer>
 */
class AssocierRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Associer::class);
    }
    public function findOneByJourAndHoraire(int $jourId, int $horaireId): ?Associer
    {
        return $this->createQueryBuilder('a')
            ->join('a.jour', 'j')
            ->join('a.horaire', 'h')
            ->where('j.idJour = :jourId')
            ->andWhere('h.idHoraire = :horaireId')
            ->setParameter('jourId', $jourId)
            ->setParameter('horaireId', $horaireId)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findOneByGarageAndJour(int $garageId, int $jourId): ?Associer
    {
        return $this->createQueryBuilder('a')
            ->join('a.jour', 'j')
            ->join('a.garage', 'g')
            ->where('g.idGarage = :garageId')
            ->andWhere('j.idJour = :jourId')
            ->setParameter('garageId', $garageId)
            ->setParameter('jourId', $jourId)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /** @return array<int, array{jourId:int, libJour:string, idHoraire:int|null, hreOuvreMatin:string|null, hreFermeMatin:string|null, hreOuvreSoir:string|null, hreFermeSoir:string|null}> */
    public function findPlanningByGarage(int $garageId): array
    {
        $rows = $this->createQueryBuilder('a')
            ->select('j.idJour AS jourId, j.libJour AS libJour, h.idHoraire AS idHoraire, h.hreOuvreMatin AS hreOuvreMatin, h.hreFermeMatin AS hreFermeMatin, h.hreOuvreSoir AS hreOuvreSoir, h.hreFermeSoir AS hreFermeSoir')
            ->join('a.jour', 'j')
            ->leftJoin('a.horaire', 'h')
            ->join('a.garage', 'g')
            ->where('g.idGarage = :garageId')
            ->setParameter('garageId', $garageId)
            ->orderBy('j.idJour', 'ASC')
            ->getQuery()
            ->getArrayResult();

        $formatTime = static function (mixed $value): ?string {
            if ($value instanceof \DateTimeInterface) {
                return $value->format('H:i');
            }
            if (is_string($value) && $value !== '') {
                // MySQL TIME may come back as "HH:MM:SS"
                return substr($value, 0, 5);
            }

            return null;
        };

        return array_map(static function (array $r) use ($formatTime): array {
            return [
                'jourId' => (int) $r['jourId'],
                'libJour' => (string) $r['libJour'],
                'idHoraire' => $r['idHoraire'] !== null ? (int) $r['idHoraire'] : null,
                'hreOuvreMatin' => $formatTime($r['hreOuvreMatin'] ?? null),
                'hreFermeMatin' => $formatTime($r['hreFermeMatin'] ?? null),
                'hreOuvreSoir' => $formatTime($r['hreOuvreSoir'] ?? null),
                'hreFermeSoir' => $formatTime($r['hreFermeSoir'] ?? null),
            ];
        }, $rows);
    }

    /** @return Horaire[] */
    public function findDistinctHorairesByGarage(int $garageId): array
    {
        return $this->createQueryBuilder('a')
            ->select('DISTINCT h')
            ->join('a.horaire', 'h')
            ->join('a.garage', 'g')
            ->where('g.idGarage = :garageId')
            ->setParameter('garageId', $garageId)
            ->orderBy('h.idHoraire', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
