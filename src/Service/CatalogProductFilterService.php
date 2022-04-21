<?php

namespace App\Service;

use DateTime;
use App\Entity\ProductSection;
use Doctrine\ORM\QueryBuilder;

/**
 * Třída řešící produktový filtr
 *
 * @package App\Service
 */
class CatalogProductFilterService
{
    private QueryBuilder $queryBuilder;
    private $section;
    private $searchPhrase;
    private $priceMin;
    private $priceMax;
    private $categoriesGrouped;

    /**
     * Načte potřebná data
     *
     * @param QueryBuilder $queryBuilder
     * @param ProductSection|null $section
     * @param string|null $searchPhrase
     * @param float|null $priceMin
     * @param float|null $priceMax
     * @param array|null $categoriesGrouped
     * @return $this
     */
    public function initialize(QueryBuilder $queryBuilder, ProductSection $section = null, string $searchPhrase = null, float $priceMin = null, float $priceMax = null, array $categoriesGrouped = null): self
    {
        $this->queryBuilder = $queryBuilder;
        $this->section = $section;
        $this->searchPhrase = $searchPhrase;
        $this->priceMin = $priceMin;
        $this->priceMax = $priceMax;
        $this->categoriesGrouped = $categoriesGrouped;

        return $this;
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
     * @return $this
     */
    public function addProductSearchConditions(): self
    {
        $this->serveProductSearchConditions();

        if($this->categoriesGrouped !== null)
        {
            $parameterNumber = 0;
            foreach ($this->categoriesGrouped as $groupName => $categories)
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
            ->setParameter('now', new DateTime('now'));

        return $this;
    }

    /**
     * Do načteného query builderu přidá podmínky pro vyhledávání a viditelnost
     */
    private function serveProductSearchConditions(): void
    {
        $this->addProductVisibilityCondition();

        $this->queryBuilder
            ->andWhere('p.id LIKE :searchPhrase OR
                        p.name LIKE :searchPhrase')
            ->setParameter('searchPhrase', '%' . $this->searchPhrase . '%')
        ;

        if($this->priceMin !== null)
        {
            $this->queryBuilder
                ->andWhere('p.priceWithVat >= :priceMin')
                ->setParameter('priceMin', $this->priceMin);
        }

        if($this->priceMax !== null)
        {
            $this->queryBuilder
                ->andWhere('p.priceWithVat <= :priceMax')
                ->setParameter('priceMax', $this->priceMax);
        }

        if($this->section !== null)
        {
            $this->queryBuilder
                ->leftJoin('p.section', 'ps')
                ->andWhere('p.section = :section')
                ->setParameter('section', $this->section);
        }
    }
}