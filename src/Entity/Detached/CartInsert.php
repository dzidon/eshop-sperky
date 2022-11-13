<?php

namespace App\Entity\Detached;

use App\Entity\Product;
use App\Validation\Compound as AssertCompound;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Třída představující akci vložení produktu do košíku.
 *
 * @package App\Entity\Detached
 */
class CartInsert
{
    /**
     * @Assert\NotNull(message="Produkt je neplatný.")
     */
    private $product;

    /**
     * @AssertCompound\ProductQuantityRequirements
     * @Assert\GreaterThanOrEqual(value=1, message="Počet kusů musí být alespoň 1.")
     */
    private $quantity = 1;

    /**
     * @Assert\NotNull(message="Některá z produktových voleb byla vybrána chybně.")
     */
    private $optionGroups;

    public function getProduct(): ?Product
    {
        return $this->product;
    }

    public function setProduct(?Product $product): self
    {
        $this->product = $product;

        return $this;
    }

    public function getQuantity(): ?int
    {
        return $this->quantity;
    }

    public function setQuantity(?int $quantity): self
    {
        $this->quantity = $quantity;

        return $this;
    }

    public function getOptionGroups(): ?array
    {
        return $this->optionGroups;
    }

    public function setOptionGroups(?array $optionGroups): self
    {
        $this->optionGroups = $optionGroups;

        return $this;
    }
}
