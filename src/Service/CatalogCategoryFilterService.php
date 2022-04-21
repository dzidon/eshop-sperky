<?php

namespace App\Service;

use App\Entity\ProductSection;
use DateTime;
use App\Entity\ProductCategory;

/**
 * Třída řešící kategorie v produktovém filtru
 *
 * @package App\Service
 */
class CatalogCategoryFilterService
{
    private array $productCountPlaceholders = [];
    private array $productCountClauses = [];

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
     * Sestaví data potřebná pro vyvolání SQL dotazu potřebného na zjištění počtu produktů v kategorii
     *
     * @param ProductCategory $category
     * @param ProductSection $section
     * @param string|null $searchPhrase
     * @param float|null $priceMin
     * @param float|null $priceMax
     * @param array|null $categoriesChosen
     */
    public function createDataForCategoryProductCountQuery(ProductCategory $category, ProductSection $section, ?string $searchPhrase, ?float $priceMin, ?float $priceMax, ?array $categoriesChosen): void
    {
        $this->productCountPlaceholders = [];
        $this->productCountClauses = [];

        $this->productCountPlaceholders['section_id'] = $section->getId();
        $this->productCountPlaceholders['now'] = (new DateTime('now'))->format('Y-m-d H:i:s');

        $this->productCountClauses['having'] = $this->getHavingClauseForProductCount($category, $categoriesChosen);

        $this->productCountClauses['searchPhrase'] = '';
        if($searchPhrase !== null)
        {
            $this->productCountClauses['searchPhrase'] = 'AND (id LIKE :search_phrase OR name LIKE :search_phrase)';
            $this->productCountPlaceholders['search_phrase'] = '%' . $searchPhrase . '%';
        }

        $this->productCountClauses['priceMin'] = '';
        if($priceMin !== null)
        {
            $this->productCountClauses['priceMin'] = 'AND price_with_vat >= :price_min';
            $this->productCountPlaceholders['price_min'] = $priceMin;
        }

        $this->productCountClauses['priceMax'] = '';
        if($priceMax !== null)
        {
            $this->productCountClauses['priceMax'] = 'AND price_with_vat <= :price_max';
            $this->productCountPlaceholders['price_max'] = $priceMax;
        }
    }

    /**
     * Sestaví SQL HAVING klauzuli pro zjištění počtu produktů v kategorii
     *
     * @param ProductCategory $currentCategory
     * @param array|null $categoriesGrouped
     * @return string
     */
    private function getHavingClauseForProductCount(ProductCategory $currentCategory, ?array $categoriesGrouped): string
    {
        $allConditions = [
            'HAVING SUM(CASE WHEN pc.id = :current_category_id THEN 1 ELSE 0 END) > 0'
        ];
        $this->productCountPlaceholders['current_category_id'] = $currentCategory->getId();

        if ($categoriesGrouped !== null)
        {
            $parameterNumber = 0;
            foreach ($categoriesGrouped as $groupName => $categories) // skupiny zaskrtnutych kategorii
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
}