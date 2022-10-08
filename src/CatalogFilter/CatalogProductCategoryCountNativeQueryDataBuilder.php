<?php

namespace App\CatalogFilter;

use App\Entity\ProductCategory;
use App\Messenger\NativeQueryData;

/**
 * Vytvoří HAVING klauzuli pro zjištění počtu produktů v kategorii v produktovém filtru.
 *
 * @package App\CatalogFilter
 */
class CatalogProductCategoryCountNativeQueryDataBuilder
{
    private ProductCategory $currentCategory;

    private ?array $categoriesChosen = null;

    private ?string $prefix = null;

    private bool $prependHaving = false;

    public function __construct(ProductCategory $currentCategory)
    {
        $this->currentCategory = $currentCategory;
    }

    public function withPrefix(?string $prefix): self
    {
        $this->prefix = $prefix;

        return $this;
    }

    public function withHaving(bool $prependHaving = true): self
    {
        $this->prependHaving = $prependHaving;

        return $this;
    }

    public function withCurrentCategory(ProductCategory $currentCategory): self
    {
        $this->currentCategory = $currentCategory;

        return $this;
    }

    public function withCategoriesChosen(?array $categoriesChosen): self
    {
        $this->categoriesChosen = $categoriesChosen;

        return $this;
    }

    public function build(): NativeQueryData
    {
        $clauses = ['SUM(CASE WHEN {prefix}.id = :current_category_id THEN 1 ELSE 0 END) > 0'];
        $placeholders['current_category_id'] = $this->currentCategory->getId();

        if ($this->categoriesChosen !== null)
        {
            $parameterNumber = 0;
            foreach ($this->categoriesChosen as $groupName => $categories) // skupiny zaskrtnutych kategorii
            {
                $andConditionExists = false;
                $orConditionExists = false;

                $categoryGroupConditions = [
                    'AND' => [],
                    'OR' => [],
                ];

                foreach ($categories as $category) // jednotlive zaskrtnute kategorie
                {
                    if($category === $this->currentCategory)
                    {
                        continue;
                    }

                    if ($groupName === $this->currentCategory->getProductCategoryGroup()->getName())
                    {
                        $categoryGroupConditions['AND'][] = sprintf('SUM(CASE WHEN {prefix}.id = :category_id%s THEN 1 ELSE 0 END) = 0', $parameterNumber);
                        $andConditionExists = true;
                    }
                    else
                    {
                        $categoryGroupConditions['OR'][] = sprintf('SUM(CASE WHEN {prefix}.id = :category_id%s THEN 1 ELSE 0 END) > 0', $parameterNumber);
                        $orConditionExists = true;
                    }

                    $placeholders[sprintf('category_id%s', $parameterNumber)] = $category->getId();
                    $parameterNumber++;
                }

                $groupAndCondition = implode(' AND ', $categoryGroupConditions['AND']);
                $groupOrCondition = sprintf('(%s)', implode(' OR ', $categoryGroupConditions['OR']));

                if($andConditionExists && $orConditionExists)
                {
                    $clauses[] = sprintf('(%s AND %s)', $groupAndCondition, $groupOrCondition);
                }
                else if($andConditionExists)
                {
                    $clauses[] = $groupAndCondition;
                }
                else if($orConditionExists)
                {
                    $clauses[] = $groupOrCondition;
                }
            }
        }

        $clause = implode(' AND ', $clauses);

        // prefix
        if ($this->prefix === null)
        {
            $clause = str_replace('{prefix}.', '', $clause);
        }
        else
        {
            $clause = str_replace('{prefix}', $this->prefix, $clause);
        }

        // having
        if ($this->prependHaving && !empty($clause))
        {
            $clause = 'HAVING ' . $clause;
        }

        return new NativeQueryData($clause, $placeholders);
    }
}