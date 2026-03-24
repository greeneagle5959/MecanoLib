<?php

namespace App\Repository;

use App\Entity\Ville;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Ville>
 */
class VilleRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Ville::class);
    }

    /**
     * @return Ville[]
     */
    public function findByNomPrefix(string $prefix, int $limit = 10): array
    {
        return $this->createQueryBuilder('v')
            ->andWhere('LOWER(v.nomVille) LIKE LOWER(:prefix)')
            ->setParameter('prefix', $prefix . '%')
            ->orderBy('v.nomVille', 'ASC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}
