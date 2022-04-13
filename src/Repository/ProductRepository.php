<?php

namespace App\Repository;

use App\Entity\Product;
use App\Entity\ProductOptionGroup;
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
        // produkt, sekce
        $queryBuilder = $this->createQueryBuilder('p')
            ->select('p, ps')
            ->leftJoin('p.section', 'ps')
        ;

        foreach ($criteria as $name => $value)
        {
            $queryBuilder->andWhere(sprintf('p.%s = :%s', $name, $name))->setParameter($name, $value);
        }

        if ($visibleOnly)
        {
            $queryBuilder = $this->filter
                ->initialize($queryBuilder)
                ->addProductVisibilityCondition()
                ->getQueryBuilder();
        }

        /** @var Product|null $product */
        $product = $queryBuilder
            ->getQuery()
            ->getOneOrNullResult();

        if ($product === null)
        {
            return null;
        }

        // kategorie produktu a ke každé kategorii její skupina
        $this->_em->createQuery('
            SELECT PARTIAL
                p.{id}, pc, pcg
            FROM 
                App\Entity\Product p
            LEFT JOIN
                p.categories pc
            LEFT JOIN
                pc.productCategoryGroup pcg
            WHERE
                p.id = :id
        ')
        ->setParameter('id', $product->getId())
        ->getResult();

        // informace o produktu a ke každé její skupina
        $this->_em->createQuery('
            SELECT PARTIAL
                p.{id}, pi, pig
            FROM 
                App\Entity\Product p
            LEFT JOIN
                p.info pi
            LEFT JOIN
                pi.productInformationGroup pig
            WHERE
                p.id = :id
        ')
        ->setParameter('id', $product->getId())
        ->getResult();

        // obrázky
        $this->_em->createQuery('
            SELECT PARTIAL
                p.{id}, pimg
            FROM 
                App\Entity\Product p
            LEFT JOIN
                p.images pimg
            WHERE
                p.id = :id
            ORDER BY
                pimg.priority DESC
        ')
        ->setParameter('id', $product->getId())
        ->getResult();

        // skupiny produktových voleb
        $this->_em->createQuery('
            SELECT PARTIAL
                p.{id}, pog
            FROM 
                App\Entity\Product p
            LEFT JOIN
                p.optionGroups pog
            WHERE
                p.id = :id
        ')
        ->setParameter('id', $product->getId())
        ->getResult();

        $optionGroupIds = array_map(function (ProductOptionGroup $optionGroup) {
            return $optionGroup->getId();
        }, $product->getOptionGroups()->getValues());

        // produktové volby
        $this->_em->createQuery('
            SELECT PARTIAL
                pog.{id}, pogo
            FROM 
                App\Entity\ProductOptionGroup pog
            LEFT JOIN
                pog.options pogo
            WHERE
                pog.id IN (:ids)
        ')
        ->setParameter('ids', $optionGroupIds)
        ->getResult();

        return $product;
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