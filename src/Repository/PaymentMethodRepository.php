<?php

namespace App\Repository;

use App\Entity\PaymentMethod;
use App\Service\SortingService;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method PaymentMethod|null find($id, $lockMode = null, $lockVersion = null)
 * @method PaymentMethod|null findOneBy(array $criteria, array $orderBy = null)
 * @method PaymentMethod[]    findAll()
 * @method PaymentMethod[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PaymentMethodRepository extends ServiceEntityRepository
{
    private SortingService $sorting;

    public function __construct(ManagerRegistry $registry, SortingService $sorting)
    {
        parent::__construct($registry, PaymentMethod::class);

        $this->sorting = $sorting;
    }

    public function getQueryForSearchAndPagination($searchPhrase = null, string $sortAttribute = null): Query
    {
        $sortData = $this->sorting->createSortData($sortAttribute, PaymentMethod::getSortData());

        return $this->createQueryBuilder('pm')

            //podminky
            ->andWhere('pm.name LIKE :name')
            ->setParameter('name', '%' . $searchPhrase . '%')

            //razeni
            ->orderBy('pm.' . $sortData['attribute'], $sortData['order'])
            ->getQuery();
    }
}
