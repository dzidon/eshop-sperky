<?php

namespace App\Entity\Detached;

use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class ProductCatalogFilter
{
    private $searchPhrase;

    /**
     * @Assert\Choice(callback={"App\Entity\Product", "getSortDataForCatalog"}, message="Zvolte platnou možnost řazení.")
     * @Assert\NotBlank
     */
    private $sortBy;

    /**
     * @Assert\Type("numeric", message="Musíte zadat číselnou hodnotu.")
     */
    private $priceMin;

    /**
     * @Assert\Type("numeric", message="Musíte zadat číselnou hodnotu.")
     */
    private $priceMax;

    /**
     * @Assert\Callback()
     */
    public function validate(ExecutionContextInterface $context)
    {
        if(is_numeric($this->priceMin) && is_numeric($this->priceMax))
        {
            if ($this->priceMin > $this->priceMax)
            {
                $context->buildViolation('Minimální cena nesmí být větší než maximální cena.')
                    ->atPath('priceMin')
                    ->addViolation();
            }
        }
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
}