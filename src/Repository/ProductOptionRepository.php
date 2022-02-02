<?php

namespace App\Repository;

use App\Entity\ProductOption;
use App\Service\SortingService;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method ProductOption|null find($id, $lockMode = null, $lockVersion = null)
 * @method ProductOption|null findOneBy(array $criteria, array $orderBy = null)
 * @method ProductOption[]    findAll()
 * @method ProductOption[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ProductOptionRepository extends ServiceEntityRepository
{
    private SortingService $sorting;

    public function __construct(ManagerRegistry $registry, SortingService $sorting)
    {
        parent::__construct($registry, ProductOption::class);

        $this->sorting = $sorting;
    }

    public function getQueryForSearchAndPagination($searchPhrase = null, string $sortAttribute = null): Query
    {
        $sortData = $this->sorting->createSortData($sortAttribute, ProductOption::getSortData());

        return $this->createQueryBuilder('po')

            //podminky
            ->orWhere('po.name LIKE :name')
            ->setParameter('name', '%' . $searchPhrase . '%')

            ->orWhere('po.type LIKE :type')
            ->setParameter('type', '%' . $searchPhrase . '%')

            //razeni
            ->orderBy('po.' . $sortData['attribute'], $sortData['order'])
            ->getQuery()
        ;
    }
}
