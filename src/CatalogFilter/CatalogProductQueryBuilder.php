<?php

namespace App\CatalogFilter;

use DateTime;
use App\Entity\ProductSection;
use Doctrine\ORM\QueryBuilder;

/**
 * Třída řešící tvorbu dotazu pro produkty v produktovém katalogu
 *
 * @package App\CatalogFilter
 */
class CatalogProductQueryBuilder
{
    private QueryBuilder $queryBuilder;

    /**
     * @param QueryBuilder $queryBuilder
     */
    public function __construct(QueryBuilder $queryBuilder)
    {
        $this->queryBuilder = $queryBuilder;
    }

    /**
     * @return QueryBuilder
     */
    public function getQueryBuilder(): QueryBuilder
    {
        return $this->queryBuilder;
    }

    /**
     * Do načteného query builderu přidá kompletní podmínky pro vyhledávání a viditelnost
     *
     * @param ProductSection|null $section
     * @param string|null $searchPhrase
     * @param float|null $priceMin
     * @param float|null $priceMax
     * @param array|null $categoriesGrouped
     * @return $this
     */
    public function addProductSearchConditions(ProductSection $section = null, string $searchPhrase = null, float $priceMin = null, float $priceMax = null, array $categoriesGrouped = null): self
    {
        $this->queryBuilder
            ->andWhere('p.name LIKE :searchPhrase')
            ->setParameter('searchPhrase', '%' . $searchPhrase . '%')
        ;

        if ($priceMin !== null)
        {
            $this->queryBuilder
                ->andWhere('p.priceWithVat >= :priceMin')
                ->setParameter('priceMin', $priceMin)
            ;
        }

        if ($priceMax !== null)
        {
            $this->queryBuilder
                ->andWhere('p.priceWithVat <= :priceMax')
                ->setParameter('priceMax', $priceMax)
            ;
        }

        if ($section !== null)
        {
            $this->queryBuilder
                ->leftJoin('p.section', 'ps')
                ->andWhere('p.section = :section')
                ->setParameter('section', $section)
            ;
        }

        if ($categoriesGrouped !== null)
        {
            $parameterNumber = 0;
            foreach ($categoriesGrouped as $groupName => $categories)
            {
                $categoryGroupConditions = [];
                foreach ($categories as $category)
                {
                    $categoryGroupConditions[] = sprintf(':category%s MEMBER OF p.categories', $parameterNumber);
                    $this->queryBuilder->setParameter(sprintf('category%s', $parameterNumber), $category);
                    $parameterNumber++;
                }

                $groupCondition = '(' . implode(' OR ', $categoryGroupConditions) . ')';
                $this->queryBuilder->andWhere($groupCondition);
            }
        }

        return $this;
    }

    /**
     * Do načteného query builderu přidá podmínky pro viditelnost
     *
     * @return $this
     */
    public function addProductVisibilityCondition(): self
    {
        $this->queryBuilder
            ->andWhere('p.isHidden = false')
            ->andWhere('NOT (p.availableSince IS NOT NULL AND p.availableSince > :now)')
            ->andWhere('NOT (p.hideWhenSoldOut = true AND p.inventory <= 0)')
            ->setParameter('now', new DateTime('now'))
        ;

        return $this;
    }
}