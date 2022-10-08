<?php

namespace App\CatalogFilter;

use App\Entity\Product;
use App\Entity\ProductSection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;

/**
 * Rozšíření Doctrine QueryBuilderu pro vyhledávání produktů.
 *
 * @package App\CatalogFilter
 */
class CatalogProductSearchQueryBuilder extends QueryBuilder
{
    private string $productPrefix;

    private string $productSectionPrefix;

    private bool $invisible = false;

    private bool $sectionSearchEnabled = false;

    private ?ProductSection $section = null;

    private ?string $searchPhrase = null;

    private ?float $priceMin = null;

    private ?float $priceMax = null;

    private ?array $categoriesGrouped = null;

    public function __construct(EntityManagerInterface $em, string $productPrefix, string $indexBy = null)
    {
        parent::__construct($em);

        $this->productPrefix = $productPrefix;

        $this->select($productPrefix)
            ->from(Product::class, $productPrefix, $indexBy)
        ;
    }

    public function withInvisible(bool $invisible = true): self
    {
        $this->invisible = $invisible;

        return $this;
    }

    public function withPrefix(string $prefix): self
    {
        $this->productPrefix = $prefix;

        return $this;
    }

    public function withSection(?ProductSection $section, string $productSectionPrefix): self
    {
        $this->section = $section;
        $this->productSectionPrefix = $productSectionPrefix;
        $this->sectionSearchEnabled = true;

        return $this;
    }

    public function withSearchPhrase(?string $searchPhrase): self
    {
        $this->searchPhrase = $searchPhrase;

        return $this;
    }

    public function withPriceMin(?float $priceMin): self
    {
        $this->priceMin = $priceMin;

        return $this;
    }

    public function withPriceMax(?float $priceMax): self
    {
        $this->priceMax = $priceMax;

        return $this;
    }

    public function withCategoriesGrouped(?array $categoriesGrouped): self
    {
        $this->categoriesGrouped = $categoriesGrouped;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getQuery(): Query
    {
        if ($this->searchPhrase !== null)
        {
            $this->andWhere($this->prefixApply('{prefix}.name LIKE :searchPhrase'))
                ->setParameter('searchPhrase', '%' . $this->searchPhrase . '%')
            ;
        }

        if ($this->priceMin !== null)
        {
            $this->andWhere($this->prefixApply('{prefix}.priceWithVat >= :priceMin'))
                ->setParameter('priceMin', $this->priceMin)
            ;
        }

        if ($this->priceMax !== null)
        {
            $this->andWhere($this->prefixApply('{prefix}.priceWithVat <= :priceMax'))
                ->setParameter('priceMax', $this->priceMax)
            ;
        }

        if ($this->sectionSearchEnabled)
        {
            $this->leftJoin($this->prefixApply('{prefix}.section'), $this->productSectionPrefix)
                ->andWhere($this->prefixApply('{prefix}.section = :section'))
                ->setParameter('section', $this->section)
            ;
        }

        if (!$this->invisible)
        {
            $this->andWhere($this->prefixApply('{prefix}.isHidden = false'))
                ->andWhere($this->prefixApply('NOT ({prefix}.availableSince IS NOT NULL AND {prefix}.availableSince > CURRENT_TIME())'))
                ->andWhere($this->prefixApply('NOT ({prefix}.hideWhenSoldOut = true AND {prefix}.inventory <= 0)'))
            ;
        }

        if ($this->categoriesGrouped !== null)
        {
            $parameterNumber = 0;
            foreach ($this->categoriesGrouped as $categories)
            {
                $categoryGroupConditions = [];
                foreach ($categories as $category)
                {
                    $categoryGroupConditions[] = $this->prefixApply(sprintf(':category%s MEMBER OF {prefix}.categories', $parameterNumber));
                    $this->setParameter(sprintf('category%s', $parameterNumber), $category);
                    $parameterNumber++;
                }

                $groupCondition = '(' . implode(' OR ', $categoryGroupConditions) . ')';
                $this->andWhere($groupCondition);
            }
        }

        return parent::getQuery();
    }

    private function prefixApply(string $clause): string
    {
        return str_replace('{prefix}', $this->productPrefix, $clause);
    }
}