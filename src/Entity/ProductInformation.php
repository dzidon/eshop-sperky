<?php

namespace App\Entity;

use App\Repository\ProductInformationRepository;
use DateTime;
use DateTimeInterface;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass=ProductInformationRepository::class)
 * @ORM\HasLifecycleCallbacks()
 */
class ProductInformation
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     *
     * @Assert\Length(max=255, maxMessage="Maximální počet znaků v hodnotě produktové informace: {{ limit }}")
     * @Assert\NotBlank
     */
    private $value;

    /**
     * @ORM\ManyToOne(targetEntity=ProductInformationGroup::class, inversedBy="info")
     * @ORM\JoinColumn(nullable=false, onDelete="CASCADE")
     */
    private $productInformationGroup;

    /**
     * @ORM\ManyToOne(targetEntity=Product::class, inversedBy="info")
     * @ORM\JoinColumn(nullable=false, onDelete="CASCADE")
     */
    private $product;

    /**
     * @ORM\Column(type="datetime")
     */
    private $created;

    /**
     * @ORM\Column(type="datetime")
     */
    private $updated;

    public function __construct()
    {
        $this->created = new DateTime('now');
        $this->updated = $this->created;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getValue(): ?string
    {
        return $this->value;
    }

    public function setValue(?string $value): self
    {
        $this->value = $value;

        return $this;
    }

    public function getProductInformationGroup(): ?ProductInformationGroup
    {
        return $this->productInformationGroup;
    }

    public function setProductInformationGroup(?ProductInformationGroup $productInformationGroup): self
    {
        $this->productInformationGroup = $productInformationGroup;

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

    public function getCreated(): ?DateTimeInterface
    {
        return $this->created;
    }

    public function setCreated(DateTimeInterface $created): self
    {
        $this->created = $created;

        return $this;
    }

    public function getUpdated(): ?DateTimeInterface
    {
        return $this->updated;
    }

    public function setUpdated(DateTimeInterface $updated): self
    {
        $this->updated = $updated;

        return $this;
    }

    /**
     * @ORM\PreUpdate
     */
    public function setUpdatedNow(): void
    {
        $this->updated = new DateTime('now');
    }
}
