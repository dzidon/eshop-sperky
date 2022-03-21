<?php

namespace App\Entity\Detached;

use App\Entity\Product;
use Symfony\Component\Validator\Constraints as Assert;

class CartInsert
{
    /**
     * @Assert\Type("integer", message="Do počtu kusů musíte zadat celé číslo.")
     * @Assert\GreaterThan(0, message="Počet kusů musí být větší než 0.")
     * @Assert\NotBlank(message="Počet kusů nesmí být prázdný.")
     */
    private $quantity;

    private $productId;

    private $product;

    private $options;

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

    public function getOptions(): ?array
    {
        return $this->options;
    }

    public function setOptions(array $options): self
    {
        $this->options = $options;

        return $this;
    }
}
