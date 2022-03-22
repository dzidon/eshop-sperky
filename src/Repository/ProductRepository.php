<?php

namespace App\Repository;

use App\Entity\Product;
use App\Entity\ProductSection;
use App\Service\ProductCatalogFilterService;
use App\Service\SortingService;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Exception;

/**
 * @method Product|null find($id, $lockMode = null, $lockVersion = null)
 * @method Product|null findOneBy(array $criteria, array $orderBy = null)
 * @method Product[]    findAll()
 * @method Product[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ProductRepository extends ServiceEntityRepository
{
    private SortingService $sorting;
    private ProductCatalogFilterService $filter;

    public function __construct(ManagerRegistry $registry, SortingService $sorting, ProductCatalogFilterService $filter)
    {
        parent::__construct($registry, Product::class);

        $this->sorting = $sorting;
        $this->filter = $filter;
    }

    public function findOneAndFetchEverything(array $criteria, bool $visibleOnly)
    {
        $queryBuilder = $this->createQueryBuilder('p')
            ->select('p, ps, pc, pcg, pog, pogo, pi, pig, pimg')
            ->leftJoin('p.section', 'ps')
            ->leftJoin('p.categories', 'pc')
            ->leftJoin('pc.productCategoryGroup', 'pcg')
            ->leftJoin('p.optionGroups', 'pog')
            ->leftJoin('pog.options', 'pogo')
            ->leftJoin('p.info', 'pi')
            ->leftJoin('pi.productInformationGroup', 'pig')
            ->leftJoin('p.images', 'pimg')
            ->addOrderBy('pimg.priority', 'DESC')
            ->addOrderBy('pc.name', 'ASC')
            ->addOrderBy('pcg.name', 'ASC')
            ->addOrderBy('pig.name', 'ASC')
            ->addOrderBy('pogo.id', 'ASC');

        foreach ($criteria as $name => $value)
        {
            $queryBuilder->andWhere(sprintf('p.%s = :%s', $name, $name))->setParameter($name, $value);
        }

        if($visibleOnly)
        {
            $queryBuilder = $this->filter
                ->initialize($queryBuilder)
                ->addProductVisibilityCondition()
                ->getQueryBuilder();
        }

        return $queryBuilder
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findLatest(int $count)
    {
        $queryBuilder = $this->createQueryBuilder('p')
            ->setMaxResults($count)
            ->orderBy('p.created', 'DESC')
        ;

        return $this->filter
            ->initialize($queryBuilder)
            ->addProductVisibilityCondition()
            ->getQueryBuilder()
            ->getQuery()
            ->getResult()
        ;
    }

    public function getMinAndMaxPrice(ProductSection $section = null)
    {
        $queryBuilder = $this->createQueryBuilder('p')
            ->select('min(p.priceWithVat) as priceMin, max(p.priceWithVat) as priceMax');

        if($section !== null)
        {
            $queryBuilder
                ->andWhere('p.section = :section')
                ->setParameter('section', $section);
        }

        $priceData = $this->filter
            ->initialize($queryBuilder)
            ->addProductVisibilityCondition()
            ->getQueryBuilder()
            ->getQuery()
            ->getScalarResult()[0];

        foreach ($priceData as $key => &$value)
        {
            if($value === null)
            {
                $value = 0;
            }
        }

        return $priceData;
    }

    public function findRelated(Product $product, int $quantity)
    {
        $productsCount = $this->getQueryForRelated($product, $quantity)
            ->select('count(p.id)')
            ->getQuery()
            ->getSingleScalarResult();

        $maxOffset = $productsCount-$quantity;
        if($maxOffset < 0)
        {
            $maxOffset = 0;
        }

        try
        {
            $randomOffset = random_int(0, $maxOffset);
        }
        catch (Exception $e)
        {
            $randomOffset = 0;
        }

        $products = $this->getQueryForRelated($product, $quantity)
            ->setFirstResult($randomOffset)
            ->getQuery()
            ->getResult();

        shuffle($products);
        return $products;
    }

    public function findOneForCartInsert($id)
    {
        $queryBuilder = $this->createQueryBuilder('p')
            ->select('p, pog, pogo')
            ->leftJoin('p.optionGroups', 'pog')
            ->leftJoin('pog.options', 'pogo')
            ->andWhere('p.id LIKE :id')
            ->setParameter('id', $id)
        ;

        return $this->filter
            ->initialize($queryBuilder)
            ->addProductVisibilityCondition()
            ->getQueryBuilder()
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    public function getQueryForSearchAndPagination(bool $inAdmin, ProductSection $section = null, string $searchPhrase = null, string $sortAttribute = null, float $priceMin = null, float $priceMax = null, array $categoriesGrouped = null): Query
    {
        $queryBuilder = $this->createQueryBuilder('p');

        if($inAdmin)
        {
            $sortData = $this->sorting->createSortData($sortAttribute, Product::getSortDataForAdmin());
            $queryBuilder
                // vyhledavani
                ->andWhere('p.id LIKE :searchPhrase OR
                            p.name LIKE :searchPhrase OR
                            p.slug LIKE :searchPhrase')
                ->setParameter('searchPhrase', '%' . $searchPhrase . '%')
            ;
        }
        else
        {
            $sortData = $this->sorting->createSortData($sortAttribute, Product::getSortDataForCatalog());
            $queryBuilder = $this->filter
                ->initialize($queryBuilder, $section, $searchPhrase, $priceMin, $priceMax, $categoriesGrouped)
                ->addProductSearchConditions()
                ->getQueryBuilder();
        }

        $queryBuilder->orderBy('p.' . $sortData['attribute'], $sortData['order']);
        return $queryBuilder->getQuery();
    }

    private function getQueryForRelated(Product $product, int $quantity): QueryBuilder
    {
        $queryBuilder = $this->createQueryBuilder('p');

        return $this->filter
            ->initialize($queryBuilder)
            ->addProductVisibilityCondition()
            ->getQueryBuilder()

            ->andWhere('p.id != :viewedProductId')
            ->andWhere('p.section = :viewedProductSection')
            ->setParameter('viewedProductId', $product->getId())
            ->setParameter('viewedProductSection', $product->getSection())
            ->orderBy('p.created', 'DESC')
            ->setMaxResults($quantity);
    }
}