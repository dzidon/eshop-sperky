<?php

namespace App\Repository;

use App\CatalogFilter\CatalogProductSearchQueryBuilder;
use App\Entity\Detached\Search\Composition\ProductFilter;
use Exception;
use App\Entity\Product;
use App\Entity\ProductSection;
use App\Pagination\Pagination;
use App\Entity\ProductOptionGroup;
use Doctrine\Persistence\ManagerRegistry;
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
    private RequestStack $requestStack;

    public function __construct(ManagerRegistry $registry, RequestStack $requestStack)
    {
        parent::__construct($registry, Product::class);

        $this->requestStack = $requestStack;
    }

    public function findOneAndFetchEverything(array $criteria, bool $visibleOnly): ?Product
    {
        // produkt, sekce
        $queryBuilder = (new CatalogProductSearchQueryBuilder($this->_em, 'p'))
            ->addSelect('ps')
            ->leftJoin('p.section', 'ps')
            ->withInvisible(!$visibleOnly)
        ;

        foreach ($criteria as $name => $value)
        {
            $queryBuilder->andWhere(sprintf('p.%s = :%s', $name, $name))->setParameter($name, $value);
        }

        /** @var Product|null $product */
        $product = $queryBuilder
            ->getQuery()
            ->getOneOrNullResult()
        ;

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
        if (count($optionGroupIds) > 0)
        {
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
        }

        return $product;
    }

    public function findLatest(int $count)
    {
        return (new CatalogProductSearchQueryBuilder($this->_em, 'p'))
            ->setMaxResults($count)
            ->orderBy('p.created', 'DESC')
            ->getQuery()
            ->getResult()
        ;
    }

    public function getMinAndMaxPrice(?ProductSection $section)
    {
        $queryBuilder = (new CatalogProductSearchQueryBuilder($this->_em, 'p'))
            ->select('min(p.priceWithVat) as priceMin, max(p.priceWithVat) as priceMax')
        ;

        if ($section !== null)
        {
            $queryBuilder->withSection($section, 'ps');
        }

        $priceData = $queryBuilder
            ->getQuery()
            ->getScalarResult()[0]
        ;

        foreach ($priceData as &$value)
        {
            if($value === null)
            {
                $value = 0;
            }
        }

        return $priceData;
    }

    public function findAllVisible()
    {
        return (new CatalogProductSearchQueryBuilder($this->_em, 'p'))
            ->getQuery()
            ->getResult()
        ;
    }

    public function findRelated(Product $product, int $quantity)
    {
        $productsCount = $this->getQueryBuilderForRelated($product, $quantity)
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

        $products = $this->getQueryBuilderForRelated($product, $quantity)
            ->setFirstResult($randomOffset)
            ->getQuery()
            ->getResult()
        ;

        shuffle($products);
        return $products;
    }

    public function findOneForCartInsert(int $id): ?Product
    {
        /** @var Product|null $product */
        $product = (new CatalogProductSearchQueryBuilder($this->_em, 'p'))
            ->select('p, pog')
            ->leftJoin('p.optionGroups', 'pog')
            ->andWhere('p.id = :id')
            ->setParameter('id', $id)
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
        if (count($optionGroupIds) > 0)
        {
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
        }

        return $product;
    }

    public function getSearchPagination(bool $inAdmin, ProductFilter $searchData): Pagination
    {
        $sortData = $searchData->getPhraseSort()->getSort()->getDqlSortData();

        if($inAdmin)
        {
            $queryBuilder = $this->createQueryBuilder('p')
                ->andWhere('p.id LIKE :searchPhrase OR
                            p.name LIKE :searchPhrase OR
                            p.slug LIKE :searchPhrase')
                ->setParameter('searchPhrase', '%' . $searchData->getPhraseSort()->getPhrase()->getText() . '%')
            ;
        }
        else
        {
            $queryBuilder = (new CatalogProductSearchQueryBuilder($this->_em, 'p'))
                ->withSearchPhrase($searchData->getPhraseSort()->getPhrase()->getText())
                ->withCategoriesGrouped($searchData->getCategoriesGrouped())
                ->withPriceMin($searchData->getPriceMin())
                ->withPriceMax($searchData->getPriceMax())
            ;

            if ($searchData->getSection() !== null)
            {
                $queryBuilder->withSection($searchData->getSection(), 'ps');
            }
        }

        $query = $queryBuilder
            ->orderBy('p.' . $sortData['attribute'], $sortData['order'])
            ->getQuery()
        ;

        return new Pagination($query, $this->requestStack->getCurrentRequest(), 12);
    }

    private function getQueryBuilderForRelated(Product $product, int $quantity): CatalogProductSearchQueryBuilder
    {
        return (new CatalogProductSearchQueryBuilder($this->_em, 'p'))
            ->andWhere('p.id != :viewedProductId')
            ->andWhere('p.section = :viewedProductSection')
            ->setParameter('viewedProductId', $product->getId())
            ->setParameter('viewedProductSection', $product->getSection())
            ->orderBy('p.created', 'DESC')
            ->setMaxResults($quantity)
        ;
    }
}