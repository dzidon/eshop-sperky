<?php

namespace App\Repository;

use App\Entity\Detached\Search\SearchAndSort;
use App\Entity\ProductOptionGroup;
use App\Pagination\Pagination;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * @method ProductOptionGroup|null find($id, $lockMode = null, $lockVersion = null)
 * @method ProductOptionGroup|null findOneBy(array $criteria, array $orderBy = null)
 * @method ProductOptionGroup[]    findAll()
 * @method ProductOptionGroup[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ProductOptionGroupRepository extends ServiceEntityRepository
{
    private $request;

    public function __construct(ManagerRegistry $registry, RequestStack $requestStack)
    {
        parent::__construct($registry, ProductOptionGroup::class);

        $this->request = $requestStack->getCurrentRequest();
    }

    public function getSearchPagination(SearchAndSort $searchData): Pagination
    {
        $sortData = $searchData->getDqlSortData();

        $query = $this->createQueryBuilder('po')

            //podminky
            ->andWhere('po.name LIKE :name')
            ->setParameter('name', '%' . $searchData->getSearchPhrase() . '%')

            //razeni
            ->orderBy('po.' . $sortData['attribute'], $sortData['order'])
            ->getQuery()
        ;

        return new Pagination($query, $this->request);
    }
}