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
    private $quantity = 1;

    /**
     * @Assert\NotBlank(message="Bylo odesláno neplatné ID produktu. Zkuste aktualizovat stránku a opakovat akci.")
     */
    private $productId;

    /**
     * @Assert\NotNull(message="Některá z produktových voleb byla vybrána chybně. Zkuste aktualizovat stránku a opakovat akci.")
     */
    private $optionGroups;

    private $product;

    public function __construct(Product $product)
    {
        $this->product = $product;
        $this->productId = $product->getId();
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

    public function getProduct(): Product
    {
        return $this->product;
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
