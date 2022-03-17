<?php

namespace App\Repository;

use App\Entity\CartOccurence;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method CartOccurence|null find($id, $lockMode = null, $lockVersion = null)
 * @method CartOccurence|null findOneBy(array $criteria, array $orderBy = null)
 * @method CartOccurence[]    findAll()
 * @method CartOccurence[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CartOccurenceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CartOccurence::class);
    }
}
