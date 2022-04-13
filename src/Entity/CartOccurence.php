<?php

namespace App\Entity;

use App\Repository\CartOccurenceRepository;
use App\Validation\Compound as AssertCompound;
use Symfony\Component\Validator\Constraints as Assert;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
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
     * @ORM\ManyToOne(targetEntity=Product::class, inversedBy="cartOccurences", cascade={"persist"})
     * @ORM\JoinColumn(onDelete="SET NULL")
     */
    private $product;

    /**
     * @ORM\ManyToMany(targetEntity=ProductOption::class)
     * @ORM\JoinTable(name="_cartoccurence_productoption")
     */
    private $options;

    /**
     * @ORM\Column(type="integer")
     *
     * @AssertCompound\ProductQuantityRequirements
     * @Assert\GreaterThanOrEqual(0)
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
    private $optionsString;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function __construct()
    {
        $this->options = new ArrayCollection();
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

    /**
     * @return Collection|ProductOption[]
     */
    public function getOptions(): Collection
    {
        return $this->options;
    }

    public function addOption(ProductOption $option): self
    {
        if (!$this->options->contains($option))
        {
            $this->options[] = $option;
        }

        return $this;
    }

    public function removeOption(ProductOption $option): self
    {
        $this->options->removeElement($option);

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

    public function addQuantity(int $quantity): self
    {
        if($this->quantity === null)
        {
            $this->quantity = $quantity;
        }
        else
        {
            $this->quantity += $quantity;
        }

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

    public function getOptionsString(): ?string
    {
        return $this->optionsString;
    }

    public function setOptionsString(?string $optionsString): self
    {
        $this->optionsString = $optionsString;

        return $this;
    }

    public function generateOptionsString(): self
    {
        $this->optionsString = null;
        foreach ($this->options as $option)
        {
            if($this->optionsString === null)
            {
                $this->optionsString = sprintf('%s: %s', $option->getProductOptionGroup()->getName(), $option->getName());
            }
            else
            {
                $this->optionsString .= sprintf(', %s: %s', $option->getProductOptionGroup()->getName(), $option->getName());
            }
        }

        return $this;
    }
}
