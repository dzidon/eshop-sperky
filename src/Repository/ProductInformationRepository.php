<?php

namespace App\Repository;

use App\Entity\ProductInformation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method ProductInformation|null find($id, $lockMode = null, $lockVersion = null)
 * @method ProductInformation|null findOneBy(array $criteria, array $orderBy = null)
 * @method ProductInformation[]    findAll()
 * @method ProductInformation[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ProductInformationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ProductInformation::class);
    }
}