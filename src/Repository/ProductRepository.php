<?php

namespace App\Repository;

use Exception;
use App\Entity\Product;
use App\Entity\ProductSection;
use App\Pagination\Pagination;
use Doctrine\ORM\QueryBuilder;
use App\Service\SortingService;
use App\Entity\ProductOptionGroup;
use Doctrine\Persistence\ManagerRegistry;
use App\CatalogFilter\CatalogProductQueryBuilder;
use Symfony\Component\HttpFoundation\RequestStack;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;

/**
 * @method Product|null find($id, $lockMode = null, $lockVersion = null)
 * @method Product|null findOneBy(array $criteria, array $orderBy = null)
 * @method Product[]    findAll()
 * @method Product[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ProductRepository extends ServiceEntityRepository
{
    private SortingService $sorting;
    private $request;

    public function __construct(ManagerRegistry $registry, SortingService $sorting, RequestStack $requestStack)
    {
        parent::__construct($registry, Product::class);

        $this->sorting = $sorting;
        $this->request = $requestStack->getCurrentRequest();
    }

    public function findOneAndFetchEverything(array $criteria, bool $visibleOnly): ?Product
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
            $queryBuilder = (new CatalogProductQueryBuilder($queryBuilder))
                ->addProductVisibilityCondition()
                ->getQueryBuilder()
            ;
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

        return (new CatalogProductQueryBuilder($queryBuilder))
            ->addProductVisibilityCondition()
            ->getQueryBuilder()
            ->getQuery()
            ->getResult()
        ;
    }

    public function getMinAndMaxPrice(?ProductSection $section)
    {
        $queryBuilder = $this->createQueryBuilder('p')
            ->select('min(p.priceWithVat) as priceMin, max(p.priceWithVat) as priceMax');

        /*if($section !== null)
        {
            $queryBuilder
                ->andWhere('p.section = :section')
                ->setParameter('section', $section);
        }*/

        $priceData = (new CatalogProductQueryBuilder($queryBuilder))
            ->addProductVisibilityCondition()
            ->addProductSearchConditions($section)
            ->getQueryBuilder()
            ->getQuery()
            ->getScalarResult()[0]
        ;

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
            ->getSingleScalarResult()
        ;

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
            ->getResult()
        ;

        shuffle($products);
        return $products;
    }

    public function findOneForCartInsert(int $id): ?Product
    {
        $queryBuilder = $this->createQueryBuilder('p')
            ->select('p, pog')
            ->leftJoin('p.optionGroups', 'pog')
            ->andWhere('p.id = :id')
            ->setParameter('id', $id)
        ;

        /** @var Product|null $product */
        $product = (new CatalogProductQueryBuilder($queryBuilder))
            ->addProductVisibilityCondition()
            ->getQueryBuilder()
            ->getQuery()
            ->getOneOrNullResult()
        ;

        if ($product === null)
        {
            return null;
        }

        $optionGroupIds = [];
        foreach ($product->getOptionGroups() as $optionGroup)
        {
            $optionGroupIds[] = $optionGroup->getId();
        }

        // ke každé skupině voleb její volby
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

    public function getSearchPagination(bool $inAdmin, ProductSection $section = null, string $searchPhrase = null, string $sortAttribute = null, float $priceMin = null, float $priceMax = null, array $categoriesGrouped = null): Pagination
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
            $queryBuilder = (new CatalogProductQueryBuilder($queryBuilder))
                ->addProductVisibilityCondition()
                ->addProductSearchConditions($section, $searchPhrase, $priceMin, $priceMax, $categoriesGrouped)
                ->getQueryBuilder()
            ;
        }

        $query = $queryBuilder
            ->orderBy('p.' . $sortData['attribute'], $sortData['order'])
            ->getQuery()
        ;

        return new Pagination($query, $this->request, 12);
    }

    private function getQueryForRelated(Product $product, int $quantity): QueryBuilder
    {
        $queryBuilder = $this->createQueryBuilder('p');

        return (new CatalogProductQueryBuilder($queryBuilder))
            ->addProductVisibilityCondition()
            ->getQueryBuilder()

            ->andWhere('p.id != :viewedProductId')
            ->andWhere('p.section = :viewedProductSection')
            ->setParameter('viewedProductId', $product->getId())
            ->setParameter('viewedProductSection', $product->getSection())
            ->orderBy('p.created', 'DESC')
            ->setMaxResults($quantity)
        ;
    }
}