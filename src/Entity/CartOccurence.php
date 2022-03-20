<?php

namespace App\Entity;

use App\Repository\CartOccurenceRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=CartOccurenceRepository::class)
 */
class CartOccurence
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity=Order::class, inversedBy="cartOccurences")
     * @ORM\JoinColumn(nullable=false, onDelete="CASCADE")
     */
    private $order_;

    /**
     * @ORM\ManyToOne(targetEntity=Product::class, inversedBy="cartOccurences")
     * @ORM\JoinColumn(onDelete="SET NULL")
     */
    private $product;

    /**
     * @ORM\Column(type="integer")
     */
    private $quantity;

    /**
     * @ORM\Column(type="float")
     */
    private $priceWithoutVat;

    /**
     * @ORM\Column(type="float")
     */
    private $priceWithVat;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $name;

    /**
     * @ORM\Column(type="string", length=500, nullable=true)
     */
    private $options;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getOrder(): ?Order
    {
        return $this->order_;
    }

    public function setOrder(?Order $order_): self
    {
        $this->order_ = $order_;

        return $this;
    }

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

    public function getPriceWithoutVat(): ?float
    {
        return $this->priceWithoutVat;
    }

    public function setPriceWithoutVat(float $priceWithoutVat): self
    {
        $this->priceWithoutVat = $priceWithoutVat;

        return $this;
    }

    public function getPriceWithVat(): ?float
    {
        return $this->priceWithVat;
    }

    public function setPriceWithVat(float $priceWithVat): self
    {
        $this->priceWithVat = $priceWithVat;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getOptions(): ?string
    {
        return $this->options;
    }

    public function setOptions(?string $options): self
    {
        $this->options = $options;

        return $this;
    }
}
