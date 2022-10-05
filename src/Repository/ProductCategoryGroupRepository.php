<?php

namespace App\Repository;

use App\Entity\Detached\Search\Composition\PhraseSort;
use App\Entity\ProductCategoryGroup;
use App\Pagination\Pagination;
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
    private RequestStack $requestStack;

    public function __construct(ManagerRegistry $registry, RequestStack $requestStack)
    {
        parent::__construct($registry, ProductCategoryGroup::class);

        $this->requestStack = $requestStack;
    }

    public function findAllAndFetchCategories()
    {
        return $this->createQueryBuilder('pcg')
            ->select("pcg, pc")
            ->leftJoin('pcg.categories', 'pc')
            ->getQuery()
            ->getResult()
        ;
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

    public function getSearchPagination(PhraseSort $searchData): Pagination
    {
        $sortData = $searchData->getSort()->getDqlSortData();

        $query = $this->createQueryBuilder('pcg')

            //podminky
            ->orWhere('pcg.name LIKE :name')
            ->setParameter('name', '%' . $searchData->getPhrase()->getText() . '%')

            //razeni
            ->orderBy('pcg.' . $sortData['attribute'], $sortData['order'])
            ->getQuery()
        ;

        return new Pagination($query, $this->requestStack->getCurrentRequest());
    }
}