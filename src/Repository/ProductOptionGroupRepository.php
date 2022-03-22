<?php

namespace App\Repository;

use App\Entity\ProductOptionGroup;
use App\Service\SortingService;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method ProductOptionGroup|null find($id, $lockMode = null, $lockVersion = null)
 * @method ProductOptionGroup|null findOneBy(array $criteria, array $orderBy = null)
 * @method ProductOptionGroup[]    findAll()
 * @method ProductOptionGroup[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ProductOptionGroupRepository extends ServiceEntityRepository
{
    private SortingService $sorting;

    public function __construct(ManagerRegistry $registry, SortingService $sorting)
    {
        parent::__construct($registry, ProductOptionGroup::class);

        $this->sorting = $sorting;
    }

    public function getQueryForSearchAndPagination($searchPhrase = null, string $sortAttribute = null): Query
    {
        $sortData = $this->sorting->createSortData($sortAttribute, ProductOptionGroup::getSortData());

        return $this->createQueryBuilder('po')

            //podminky
            ->andWhere('po.name LIKE :name')
            ->setParameter('name', '%' . $searchPhrase . '%')

            //razeni
            ->orderBy('po.' . $sortData['attribute'], $sortData['order'])
            ->getQuery()
        ;
    }
}