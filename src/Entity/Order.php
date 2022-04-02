<?php

namespace App\Entity;

use DateTime;
use DateTimeInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;
use App\Validation as AssertCustom;
use App\Repository\OrderRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=OrderRepository::class)
 * @ORM\Table(name="order_")
 * @ORM\HasLifecycleCallbacks()
 */
class Order
{
    public const LIFETIME_IN_DAYS = 60;
    public const REFRESH_WINDOW_IN_DAYS = 30;

    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="uuid")
     */
    private $token;

    /**
     * @ORM\Column(type="datetime")
     */
    private $expireAt;

    /**
     * @ORM\OneToMany(targetEntity=CartOccurence::class, mappedBy="order_", orphanRemoval=true, cascade={"persist"})
     *
     * @AssertCustom\CartOccurenceQuantity
     * @Assert\Valid
     */
    private $cartOccurences;

    /**
     * @ORM\Column(type="boolean")
     */
    private bool $createdManually = false;

    /**
     * @ORM\Column(type="boolean")
     */
    private bool $finished = false;

    /**
     * @ORM\ManyToOne(targetEntity=DeliveryMethod::class, inversedBy="orders")
     * @ORM\JoinColumn(onDelete="SET NULL")
     *
     * @Assert\NotBlank
     */
    private $deliveryMethod;

    /**
     * @ORM\ManyToOne(targetEntity=PaymentMethod::class, inversedBy="orders")
     * @ORM\JoinColumn(onDelete="SET NULL")
     *
     * @Assert\NotBlank
     */
    private $paymentMethod;

    /**
     * @ORM\Column(type="float")
     */
    private $deliveryPriceWithoutVat = 0.0;

    /**
     * @ORM\Column(type="float")
     */
    private $deliveryPriceWithVat = 0.0;

    /**
     * @ORM\Column(type="float")
     */
    private $paymentPriceWithoutVat = 0.0;

    /**
     * @ORM\Column(type="float")
     */
    private $paymentPriceWithVat = 0.0;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $deliveryMethodName = null;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $paymentMethodName = null;

    private int $totalQuantity = 0;
    private float $totalPriceWithoutVat = 0.0;
    private float $totalPriceWithVat = 0.0;

    public function __construct()
    {
        $this->token = Uuid::v4();
        $this->cartOccurences = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getToken(): ?Uuid
    {
        return $this->token;
    }

    public function setToken(Uuid $token): self
    {
        $this->token = $token;

        return $this;
    }

    public function getExpireAt(): ?DateTimeInterface
    {
        return $this->expireAt;
    }

    public function setExpireAt(DateTimeInterface $expireAt): self
    {
        $this->expireAt = $expireAt;

        return $this;
    }

    public function setExpireAtBasedOnLifetime(): self
    {
        $this->expireAt = (new DateTime('now'))->modify(sprintf('+%d day', Order::LIFETIME_IN_DAYS));

        return $this;
    }

    /**
     * @return Collection|CartOccurence[]
     */
    public function getCartOccurences(): Collection
    {
        return $this->cartOccurences;
    }

    public function addCartOccurence(CartOccurence $cartOccurence): self
    {
        if (!$this->cartOccurences->contains($cartOccurence)) {
            $this->cartOccurences[] = $cartOccurence;
            $cartOccurence->setOrder($this);
        }

        return $this;
    }

    public function removeCartOccurence(CartOccurence $cartOccurence): self
    {
        if ($this->cartOccurences->removeElement($cartOccurence)) {
            // set the owning side to null (unless already changed)
            if ($cartOccurence->getOrder() === $this) {
                $cartOccurence->setOrder(null);
            }
        }

        return $this;
    }

    public function isCreatedManually(): bool
    {
        return $this->createdManually;
    }

    public function setCreatedManually(bool $createdManually): self
    {
        $this->createdManually = $createdManually;

        return $this;
    }

    public function isFinished(): bool
    {
        return $this->finished;
    }

    public function setFinished(bool $finished): self
    {
        $this->finished = $finished;

        return $this;
    }

    public function hasAllRequirements(): bool
    {
        // platební metoda, doručovací metoda a alespoň jeden cart occurence s quantity > 0

        return true;
    }

    public function getDeliveryMethod(): ?DeliveryMethod
    {
        return $this->deliveryMethod;
    }

    public function setDeliveryMethod(?DeliveryMethod $deliveryMethod): self
    {
        $this->deliveryMethod = $deliveryMethod;

        return $this;
    }


    public function getPaymentMethod(): ?PaymentMethod
    {
        return $this->paymentMethod;
    }

    public function setPaymentMethod(?PaymentMethod $paymentMethod): self
    {
        $this->paymentMethod = $paymentMethod;

        return $this;
    }

    public function getTotalQuantity(): int
    {
        return $this->totalQuantity;
    }

    public function getTotalPriceWithVat(bool $withMethods = false): float
    {
        $totalPriceWithVat = $this->totalPriceWithVat;
        if ($withMethods)
        {
            $totalPriceWithVat += $this->deliveryPriceWithVat;
            $totalPriceWithVat += $this->paymentPriceWithVat;
        }

        return $totalPriceWithVat;
    }

    public function getTotalPriceWithoutVat(bool $withMethods = false): float
    {
        $totalPriceWithoutVat = $this->totalPriceWithoutVat;
        if ($withMethods)
        {
            $totalPriceWithoutVat += $this->deliveryPriceWithoutVat;
            $totalPriceWithoutVat += $this->paymentPriceWithoutVat;
        }

        return $totalPriceWithoutVat;
    }

    public function calculateTotals(): void
    {
        $this->totalQuantity = 0;
        $this->totalPriceWithVat = 0.0;
        $this->totalPriceWithoutVat = 0.0;

        foreach ($this->cartOccurences as $cartOccurence)
        {
            $this->totalQuantity += $cartOccurence->getQuantity();
            $this->totalPriceWithVat += $cartOccurence->getQuantity() * $cartOccurence->getPriceWithVat();
            $this->totalPriceWithoutVat += $cartOccurence->getQuantity() * $cartOccurence->getPriceWithoutVat();
        }
    }

    public function getDeliveryPriceWithoutVat(): ?float
    {
        return $this->deliveryPriceWithoutVat;
    }

    public function setDeliveryPriceWithoutVat(float $deliveryPriceWithoutVat): self
    {
        $this->deliveryPriceWithoutVat = $deliveryPriceWithoutVat;

        return $this;
    }

    public function getDeliveryPriceWithVat(): ?float
    {
        return $this->deliveryPriceWithVat;
    }

    public function setDeliveryPriceWithVat(float $deliveryPriceWithVat): self
    {
        $this->deliveryPriceWithVat = $deliveryPriceWithVat;

        return $this;
    }

    public function getPaymentPriceWithoutVat(): ?float
    {
        return $this->paymentPriceWithoutVat;
    }

    public function setPaymentPriceWithoutVat(float $paymentPriceWithoutVat): self
    {
        $this->paymentPriceWithoutVat = $paymentPriceWithoutVat;

        return $this;
    }

    public function getPaymentPriceWithVat(): ?float
    {
        return $this->paymentPriceWithVat;
    }

    public function setPaymentPriceWithVat(float $paymentPriceWithVat): self
    {
        $this->paymentPriceWithVat = $paymentPriceWithVat;

        return $this;
    }

    public function getDeliveryMethodName(): ?string
    {
        return $this->deliveryMethodName;
    }

    public function setDeliveryMethodName(?string $deliveryMethodName): self
    {
        $this->deliveryMethodName = $deliveryMethodName;

        return $this;
    }

    public function getPaymentMethodName(): ?string
    {
        return $this->paymentMethodName;
    }

    public function setPaymentMethodName(?string $paymentMethodName): self
    {
        $this->paymentMethodName = $paymentMethodName;

        return $this;
    }

    /**
     * @ORM\PreFlush
     */
    public function setPricesOfMethods(): self
    {
        if ($this->deliveryMethod === null)
        {
            $this->deliveryPriceWithoutVat = 0.0;
            $this->deliveryPriceWithVat = 0.0;
            $this->deliveryMethodName = null;
        }
        else
        {
            $this->deliveryPriceWithoutVat = $this->deliveryMethod->getPriceWithoutVat();
            $this->deliveryPriceWithVat = $this->deliveryMethod->getPriceWithVat();
            $this->deliveryMethodName = $this->deliveryMethod->getName();
        }

        if ($this->paymentMethod === null)
        {
            $this->paymentPriceWithoutVat = 0.0;
            $this->paymentPriceWithVat = 0.0;
            $this->paymentMethodName = null;
        }
        else
        {
            $this->paymentPriceWithoutVat = $this->paymentMethod->getPriceWithoutVat();
            $this->paymentPriceWithVat = $this->paymentMethod->getPriceWithVat();
            $this->paymentMethodName = $this->paymentMethod->getName();
        }

        return $this;
    }
}