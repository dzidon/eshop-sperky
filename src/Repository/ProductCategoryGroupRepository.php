<?php

namespace App\Repository;

use App\Entity\ProductCategoryGroup;
use App\Pagination\Pagination;
use App\Service\SortingService;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * @method ProductCategoryGroup|null find($id, $lockMode = null, $lockVersion = null)
 * @method ProductCategoryGroup|null findOneBy(array $criteria, array $orderBy = null)
 * @method ProductCategoryGroup[]    findAll()
 * @method ProductCategoryGroup[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ProductCategoryGroupRepository extends ServiceEntityRepository
{
    private SortingService $sorting;
    private $request;

    public function __construct(ManagerRegistry $registry, SortingService $sorting, RequestStack $requestStack)
    {
        parent::__construct($registry, ProductCategoryGroup::class);

        $this->sorting = $sorting;
        $this->request = $requestStack->getCurrentRequest();
    }

    public function findOneByIdAndFetchCategories($id)
    {
        return $this->createQueryBuilder('pcg')
            ->select("pcg, pc")
            ->leftJoin('pcg.categories', 'pc')
            ->andWhere('pcg.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    public function findOneByNameAndFetchCategories($name)
    {
        return $this->createQueryBuilder('pcg')
            ->select("pcg, pc")
            ->leftJoin('pcg.categories', 'pc')
            ->andWhere('pcg.name = :name')
            ->setParameter('name', $name)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    public function getSearchPagination($searchPhrase = null, string $sortAttribute = null): Pagination
    {
        $sortData = $this->sorting->createSortData($sortAttribute, ProductCategoryGroup::getSortData());

        $query = $this->createQueryBuilder('pcg')

            //podminky
            ->orWhere('pcg.name LIKE :name')
            ->setParameter('name', '%' . $searchPhrase . '%')

            //razeni
            ->orderBy('pcg.' . $sortData['attribute'], $sortData['order'])
            ->getQuery()
        ;

        return new Pagination($query, $this->request);
    }

    public function getArrayOfNames(): array
    {
        $arrayOfAllData = $this->createQueryBuilder('pcg', 'pcg.name')
            ->getQuery()
            ->getArrayResult();

        return array_keys($arrayOfAllData); // chceme jen názvy, ty jsou v klíčích
    }
}