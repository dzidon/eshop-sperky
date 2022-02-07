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

    // /**
    //  * @return ProductInformation[] Returns an array of ProductInformation objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('p.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?ProductInformation
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
