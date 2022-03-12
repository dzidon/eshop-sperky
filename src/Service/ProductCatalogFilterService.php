<?php

namespace App\Service;

use App\Entity\ProductCategory;
use App\Entity\ProductSection;
use DateTime;
use Doctrine\ORM\QueryBuilder;

/**
 * Třída řešící produktový filtr
 *
 * @package App\Service
 */
class ProductCatalogFilterService
{
    private $queryBuilder;
    private $searchPhrase;
    private $priceMin;
    private $priceMax;
    private $section;
    private $categoriesGrouped;

    private array $productCountPlaceholders = [];
    private array $productCountClauses = [];

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

    /**
     * Sestaví SQL HAVING klauzuli pro zjištění počtu produktů v kategorii
     *
     * @param ProductCategory $currentCategory
     * @return string
     */
    private function getHavingClauseForProductCount(ProductCategory $currentCategory): string
    {
        $allConditions = [
            'HAVING SUM(CASE WHEN pc.id = :current_category_id THEN 1 ELSE 0 END) > 0'
        ];
        $this->productCountPlaceholders['current_category_id'] = $currentCategory->getId();

        if ($this->categoriesGrouped !== null)
        {
            $parameterNumber = 0;
            foreach ($this->categoriesGrouped as $groupName => $categories) // skupiny zaskrtnutych kategorii
            {
                $andConditionExists = false;
                $orConditionExists = false;

                $categoryGroupConditions = [
                    'AND' => [],
                    'OR' => [],
                ];

                foreach ($categories as $category) // jednotlive zaskrtnute kategorie
                {
                    if($category === $currentCategory)
                    {
                        continue;
                    }

                    if ($groupName === $currentCategory->getProductCategoryGroup()->getName())
                    {
                        $categoryGroupConditions['AND'][] = sprintf('SUM(CASE WHEN pc.id = :id%s THEN 1 ELSE 0 END) = 0', $parameterNumber);
                        $andConditionExists = true;
                    }
                    else
                    {
                        $categoryGroupConditions['OR'][] = sprintf('SUM(CASE WHEN pc.id = :id%s THEN 1 ELSE 0 END) > 0', $parameterNumber);
                        $orConditionExists = true;
                    }

                    $this->productCountPlaceholders[sprintf('id%s', $parameterNumber)] = $category->getId();
                    $parameterNumber++;
                }

                $groupAndCondition = implode(' AND ', $categoryGroupConditions['AND']);
                $groupOrCondition = sprintf('(%s)', implode(' OR ', $categoryGroupConditions['OR']));

                if($andConditionExists && $orConditionExists)
                {
                    $allConditions[] = sprintf('(%s AND %s)', $groupAndCondition, $groupOrCondition);
                }
                else if($andConditionExists)
                {
                    $allConditions[] = $groupAndCondition;
                }
                else if($orConditionExists)
                {
                    $allConditions[] = $groupOrCondition;
                }
            }
        }

        return implode(' AND ', $allConditions);
    }

    /**
     * Načte potřebná data
     *
     * @param QueryBuilder|null $queryBuilder
     * @param ProductSection|null $section
     * @param string|null $searchPhrase
     * @param float|null $priceMin
     * @param float|null $priceMax
     * @param array|null $categoriesGrouped
     * @return $this
     */
    public function initialize(QueryBuilder $queryBuilder = null, ProductSection $section = null, string $searchPhrase = null, float $priceMin = null, float $priceMax = null, array $categoriesGrouped = null): self
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
     * @return array
     */
    public function getProductCountPlaceholders(): array
    {
        return $this->productCountPlaceholders;
    }

    /**
     * @return array
     */
    public function getProductCountClauses(): array
    {
        return $this->productCountClauses;
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
     * Sestaví data potřebná pro vyvolání SQL dotazu potřebného na zjištění počtu produktů v kategorii
     *
     * @param ProductCategory $category
     */
    public function createDataForCategoryProductCount(ProductCategory $category): void
    {
        $this->productCountPlaceholders = [];
        $this->productCountClauses = [];

        $this->productCountPlaceholders['section_id'] = $this->section->getId();
        $this->productCountPlaceholders['now'] = (new DateTime('now'))->format('Y-m-d H:i:s');

        $this->productCountClauses['having'] = $this->getHavingClauseForProductCount($category);

        $this->productCountClauses['searchPhrase'] = '';
        if($this->searchPhrase !== null)
        {
            $this->productCountClauses['searchPhrase'] = 'AND (id LIKE :search_phrase OR name LIKE :search_phrase)';
            $this->productCountPlaceholders['search_phrase'] = '%' . $this->searchPhrase . '%';
        }

        $this->productCountClauses['priceMin'] = '';
        if($this->priceMin !== null)
        {
            $this->productCountClauses['priceMin'] = 'AND price_with_vat >= :price_min';
            $this->productCountPlaceholders['price_min'] = $this->priceMin;
        }

        $this->productCountClauses['priceMax'] = '';
        if($this->priceMax !== null)
        {
            $this->productCountClauses['priceMax'] = 'AND price_with_vat <= :price_max';
            $this->productCountPlaceholders['price_max'] = $this->priceMax;
        }
    }
}