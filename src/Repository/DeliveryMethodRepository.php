<?php

namespace App\Repository;

use App\Entity\DeliveryMethod;
use App\Service\SortingService;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method DeliveryMethod|null find($id, $lockMode = null, $lockVersion = null)
 * @method DeliveryMethod|null findOneBy(array $criteria, array $orderBy = null)
 * @method DeliveryMethod[]    findAll()
 * @method DeliveryMethod[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class DeliveryMethodRepository extends ServiceEntityRepository
{
    private SortingService $sorting;

    public function __construct(ManagerRegistry $registry, SortingService $sorting)
    {
        parent::__construct($registry, DeliveryMethod::class);

        $this->sorting = $sorting;
    }

    public function getQueryForSearchAndPagination($searchPhrase = null, string $sortAttribute = null): Query
    {
        $sortData = $this->sorting->createSortData($sortAttribute, DeliveryMethod::getSortData());

        return $this->createQueryBuilder('dm')

            //podminky
            ->andWhere('dm.name LIKE :name')
            ->setParameter('name', '%' . $searchPhrase . '%')

            //razeni
            ->orderBy('dm.' . $sortData['attribute'], $sortData['order'])
            ->getQuery();
    }
}
