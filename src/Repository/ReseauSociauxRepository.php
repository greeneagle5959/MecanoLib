<?php

namespace App\Repository;

use App\Entity\ReseauSociaux;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ReseauSociaux>
 */
class ReseauSociauxRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ReseauSociaux::class);
    }
}
