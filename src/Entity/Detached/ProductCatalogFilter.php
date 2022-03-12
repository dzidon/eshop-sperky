<?php

namespace App\Entity\Detached;

use App\Entity\ProductCategory;
use App\Entity\ProductSection;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Validator\Constraints as Assert;

class ProductCatalogFilter
{
    private $searchPhrase;

    /**
     * @Assert\Choice(callback={"App\Entity\Product", "getSortDataForCatalog"})
     * @Assert\NotBlank
     */
    private $sortBy;

    /**
     * @Assert\Type("numeric")
     */
    private $priceMin;

    /**
     * @Assert\Type("numeric")
     */
    private $priceMax;

    private $section;

    private $categories;

    public function __construct()
    {
        $this->categories = new ArrayCollection();
    }

    public function getSearchPhrase(): ?string
    {
        return $this->searchPhrase;
    }

    public function setSearchPhrase(?string $searchPhrase): self
    {
        $this->searchPhrase = $searchPhrase;

        return $this;
    }

    public function getSortBy(): ?string
    {
        return $this->sortBy;
    }

    public function setSortBy(?string $sortBy): self
    {
        $this->sortBy = $sortBy;

        return $this;
    }

    public function getPriceMin(): ?float
    {
        return $this->priceMin;
    }

    public function setPriceMin(?float $priceMin): self
    {
        if($priceMin !== null)
        {
            $this->priceMin = $priceMin;
        }

        return $this;
    }

    public function getPriceMax(): ?float
    {
        return $this->priceMax;
    }

    public function setPriceMax(?float $priceMax): self
    {
        if($priceMax !== null)
        {
            $this->priceMax = $priceMax;
        }

        return $this;
    }

    public function getSection(): ?ProductSection
    {
        return $this->section;
    }

    public function setSection(?ProductSection $section): self
    {
        $this->section = $section;

        return $this;
    }

    /**
     * @return Collection|ProductCategory[]
     */
    public function getCategories(): Collection
    {
        return $this->categories;
    }

    public function getCategoriesGrouped(): ?array
    {
        if($this->categories === null || $this->categories->isEmpty())
        {
            return null;
        }

        $categoriesGrouped = [];
        foreach ($this->categories as $category)
        {
            $categoryGroupName = $category->getProductCategoryGroup()->getName();
            $categoriesGrouped[$categoryGroupName][] = $category;
        }

        return $categoriesGrouped;
    }

    public function addCategory(ProductCategory $category): self
    {
        if (!$this->categories->contains($category)) {
            $this->categories[] = $category;
        }

        return $this;
    }

    public function removeCategory(ProductCategory $category): self
    {
        $this->categories->removeElement($category);

        return $this;
    }
}