<?php

namespace App\Repository;

use App\Entity\Detached\Search\SearchAndSort;
use App\Entity\PaymentMethod;
use App\Pagination\Pagination;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * @method PaymentMethod|null find($id, $lockMode = null, $lockVersion = null)
 * @method PaymentMethod|null findOneBy(array $criteria, array $orderBy = null)
 * @method PaymentMethod[]    findAll()
 * @method PaymentMethod[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PaymentMethodRepository extends ServiceEntityRepository
{
    private $request;

    public function __construct(ManagerRegistry $registry, RequestStack $requestStack)
    {
        parent::__construct($registry, PaymentMethod::class);

        $this->request = $requestStack->getCurrentRequest();
    }

    public function getSearchPagination(SearchAndSort $searchData): Pagination
    {
        $sortData = $searchData->getDqlSortData();

        $query = $this->createQueryBuilder('pm')

            //podminky
            ->andWhere('pm.name LIKE :name')
            ->setParameter('name', '%' . $searchData->getSearchPhrase() . '%')

            //razeni
            ->orderBy('pm.' . $sortData['attribute'], $sortData['order'])
            ->getQuery()
        ;

        return new Pagination($query, $this->request);
    }
}
