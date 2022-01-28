<?php

namespace App\Repository;

use App\Entity\ProductSection;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method ProductSection|null find($id, $lockMode = null, $lockVersion = null)
 * @method ProductSection|null findOneBy(array $criteria, array $orderBy = null)
 * @method ProductSection[]    findAll()
 * @method ProductSection[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ProductSectionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ProductSection::class);
    }

    public function findAllVisible()
    {
        return $this->createQueryBuilder('ps')
            ->andWhere('ps.hidden = 0')
            ->orderBy('ps.name', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

    // /**
    //  * @return ProductSection[] Returns an array of ProductSection objects
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
    public function findOneBySomeField($value): ?ProductSection
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
