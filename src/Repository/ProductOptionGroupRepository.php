<?php

namespace App\Repository;

use App\Entity\ProductOptionGroup;
use App\Pagination\Pagination;
use App\Service\SortingService;
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
    private SortingService $sorting;
    private $request;

    public function __construct(ManagerRegistry $registry, SortingService $sorting, RequestStack $requestStack)
    {
        parent::__construct($registry, ProductOptionGroup::class);

        $this->sorting = $sorting;
        $this->request = $requestStack->getCurrentRequest();
    }

    public function getSearchPagination($searchPhrase = null, string $sortAttribute = null): Pagination
    {
        $sortData = $this->sorting->createSortData($sortAttribute, ProductOptionGroup::getSortData());

        $query = $this->createQueryBuilder('po')

            //podminky
            ->andWhere('po.name LIKE :name')
            ->setParameter('name', '%' . $searchPhrase . '%')

            //razeni
            ->orderBy('po.' . $sortData['attribute'], $sortData['order'])
            ->getQuery()
        ;

        return new Pagination($query, $this->request);
    }
}