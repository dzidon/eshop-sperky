<?php

namespace App\Service;

use App\Entity\ProductSection;
use DateTime;
use Doctrine\ORM\QueryBuilder;

class ProductCatalogFilterService
{
    public function addProductSearchConditions(QueryBuilder $queryBuilder, string $searchPhrase = null, float $priceMin = null, float $priceMax = null, ProductSection $section = null, array $categoriesGrouped = null): void
    {
        $this->addProductVisibilityCondition($queryBuilder);

        $queryBuilder
            ->andWhere('p.id LIKE :searchPhrase OR
                        p.name LIKE :searchPhrase')
            ->setParameter('searchPhrase', '%' . $searchPhrase . '%')
        ;

        if($priceMin !== null)
        {
            $queryBuilder
                ->andWhere('p.priceWithVat >= :priceMin')
                ->setParameter('priceMin', $priceMin);
        }

        if($priceMax !== null)
        {
            $queryBuilder
                ->andWhere('p.priceWithVat <= :priceMax')
                ->setParameter('priceMax', $priceMax);
        }

        if($section !== null)
        {
            $queryBuilder
                ->leftJoin('p.section', 'ps')
                ->andWhere('p.section = :section')
                ->setParameter('section', $section);
        }

        if($categoriesGrouped !== null)
        {
            $parameterNumber = 0;
            foreach ($categoriesGrouped as $groupName => $categories)
            {
                $categoryGroupConditions = [];
                foreach ($categories as $category)
                {
                    $categoryGroupConditions[] = sprintf(':category%s MEMBER OF p.categories', $parameterNumber);
                    $queryBuilder->setParameter(sprintf('category%s', $parameterNumber), $category);
                    $parameterNumber++;
                }

                $groupCondition = '(' . implode(' OR ', $categoryGroupConditions) . ')';
                $queryBuilder->andWhere($groupCondition);
            }
        }
    }

    public function addProductVisibilityCondition(QueryBuilder $queryBuilder): void
    {
        $queryBuilder
            ->andWhere('p.isHidden = false')
            ->andWhere('NOT (p.availableSince IS NOT NULL AND p.availableSince > :now)')
            ->andWhere('NOT (p.hideWhenSoldOut = true AND p.inventory <= 0)')
            ->setParameter('now', new DateTime('now'));
    }
}