<?php

namespace App\Repository;

use App\Entity\ProductCategory;
use App\Service\ProductCatalogFilterService;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method ProductCategory|null find($id, $lockMode = null, $lockVersion = null)
 * @method ProductCategory|null findOneBy(array $criteria, array $orderBy = null)
 * @method ProductCategory[]    findAll()
 * @method ProductCategory[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ProductCategoryRepository extends ServiceEntityRepository
{
    private ProductCatalogFilterService $filter;

    public function __construct(ManagerRegistry $registry, ProductCatalogFilterService $filter)
    {
        parent::__construct($registry, ProductCategory::class);

        $this->filter = $filter;
    }

    public function getNumberOfProductsForFilter(ProductCategory $category, $categoriesChosen, $section)
    {
        $queryBuilder = $this->createQueryBuilder('pc')
            ->select('count(p)')
            ->innerJoin('pc.products', 'p')
            ->andWhere('pc.id = :id')
            ->setParameter(':id', $category->getId())
        ;

        return $this->filter
            ->initialize($queryBuilder, $section, null, null, null, $categoriesChosen)
            ->addCategoryProductCountConditions($category)
            ->getQueryBuilder()
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function qbFindCategoriesInSection($section = null): QueryBuilder
    {
        $queryBuilder = $this->createQueryBuilder('pc')
            ->select('pc', 'pcg', 'p')
            ->innerJoin('pc.productCategoryGroup', 'pcg')
            ->innerJoin('pc.products', 'p')
        ;

        return $this->filter
            ->initialize($queryBuilder, $section)
            ->addProductSearchConditions()
            ->getQueryBuilder();
    }

    public function qbFindAllAndFetchGroups(): QueryBuilder
    {
        return $this->createQueryBuilder('pc')
            ->select('pc', 'pcg')
            ->innerJoin('pc.productCategoryGroup', 'pcg')
        ;
    }
}