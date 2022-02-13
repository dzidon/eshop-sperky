<?php

namespace App\Repository;

use App\Entity\ProductInformationGroup;
use App\Service\SortingService;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method ProductInformationGroup|null find($id, $lockMode = null, $lockVersion = null)
 * @method ProductInformationGroup|null findOneBy(array $criteria, array $orderBy = null)
 * @method ProductInformationGroup[]    findAll()
 * @method ProductInformationGroup[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ProductInformationGroupRepository extends ServiceEntityRepository
{
    private SortingService $sorting;

    public function __construct(ManagerRegistry $registry, SortingService $sorting)
    {
        parent::__construct($registry, ProductInformationGroup::class);

        $this->sorting = $sorting;
    }

    public function getQueryForSearchAndPagination($searchPhrase = null, string $sortAttribute = null): Query
    {
        $sortData = $this->sorting->createSortData($sortAttribute, ProductInformationGroup::getSortData());

        return $this->createQueryBuilder('pig')

            //podminky
            ->orWhere('pig.name LIKE :name')
            ->setParameter('name', '%' . $searchPhrase . '%')

            //razeni
            ->orderBy('pig.' . $sortData['attribute'], $sortData['order'])
            ->getQuery()
        ;
    }

    public function getArrayOfNames(): array
    {
        $arrayOfAllData = $this->createQueryBuilder('pig', 'pig.name')
            ->getQuery()
            ->getArrayResult();

        return array_keys($arrayOfAllData); // chceme jen názvy, ty jsou v klíčích
    }
}