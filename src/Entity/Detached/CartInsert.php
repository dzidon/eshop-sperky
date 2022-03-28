<?php

namespace App\Entity\Detached;

use App\Entity\Product;
use App\Validation\Compound as AssertCompound;
use Symfony\Component\Validator\Constraints as Assert;

class CartInsert
{
    /**
     * @AssertCompound\ProductQuantityRequirements
     * @Assert\GreaterThanOrEqual(1)
     */
    private $quantity;

    private $productId;

    private $product;

    private $optionGroups;

    public function __construct()
    {
        $this->quantity = 1;
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

    public function getProductId(): ?int
    {
        return $this->productId;
    }

    public function setProductId(?int $productId): self
    {
        $this->productId = $productId;

        return $this;
    }

    public function getProduct(): ?Product
    {
        return $this->product;
    }

    public function setProduct(Product $product): self
    {
        $this->product = $product;
        $this->productId = $product->getId();

        return $this;
    }

    public function getOptionGroups(): array
    {
        return $this->optionGroups;
    }

    public function setOptionGroups(array $optionGroups): self
    {
        $this->optionGroups = $optionGroups;

        return $this;
    }
}
