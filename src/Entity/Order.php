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

    const DELIVERY_METHODS_THAT_LOCK_ADDRESS = [
        DeliveryMethod::TYPE_PACKETA_CZ => true,
    ];

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
     * @AssertCustom\CartOccurenceQuantity(groups={"cart"})
     * @Assert\Valid(groups={"cart"})
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
     */
    private $deliveryMethod;

    /**
     * @ORM\ManyToOne(targetEntity=PaymentMethod::class, inversedBy="orders")
     * @ORM\JoinColumn(onDelete="SET NULL")
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

    /*
     * Doručovací adresa
     */

    /**
     * @ORM\Column(type="boolean")
     */
    private $addressDeliveryLocked = false;

    /**
     * @ORM\Column(type="string", length=32)
     *
     * @Assert\Choice(choices=Address::COUNTRY_NAMES, groups={"addresses_delivery"}, message="Zvolte platnou zemi.")
     * @Assert\NotBlank(groups={"addresses_delivery"})
     */
    private $addressDeliveryCountry;

    /**
     * @ORM\Column(type="string", length=255)
     *
     * @AssertCustom\Compound\StreetRequirements(groups={"addresses_delivery"})
     * @Assert\NotBlank(groups={"addresses_delivery"})
     */
    private $addressDeliveryStreet;

    /**
     * @ORM\Column(type="string", length=255)
     *
     * @Assert\Length(max=255, groups={"addresses_delivery"}, maxMessage="Maximální počet znaků v obci: {{ limit }}")
     * @Assert\NotBlank(groups={"addresses_delivery"})
     */
    private $addressDeliveryTown;

    /**
     * @ORM\Column(type="string", length=5)
     *
     * @AssertCustom\ZipCode(groups={"addresses_delivery"})
     * @Assert\NotBlank(groups={"addresses_delivery"})
     */
    private $addressDeliveryZip;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     *
     * @Assert\Length(max=255, groups={"addresses_delivery"}, maxMessage="Maximální počet znaků v doplňku adresy: {{ limit }}")
     */
    private $addressDeliveryAdditionalInfo;

    private $staticAddressDeliveryCountry;
    private $staticAddressDeliveryStreet;
    private $staticAddressDeliveryTown;
    private $staticAddressDeliveryZip;
    private $staticAddressDeliveryAdditionalInfo;

    private $previousDeliveryType;
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

    public function reindexCartOccurences(): self
    {
        $this->cartOccurences = new ArrayCollection($this->cartOccurences->getValues());

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

    public function isAddressDeliveryLocked(): ?bool
    {
        return $this->addressDeliveryLocked;
    }

    public function setAddressDeliveryLocked(bool $addressDeliveryLocked): self
    {
        $this->addressDeliveryLocked = $addressDeliveryLocked;

        return $this;
    }

    public function getAddressDeliveryAdditionalInfo(): ?string
    {
        return $this->addressDeliveryAdditionalInfo;
    }

    public function setAddressDeliveryAdditionalInfo(?string $addressDeliveryAdditionalInfo): self
    {
        $this->addressDeliveryAdditionalInfo = $addressDeliveryAdditionalInfo;

        return $this;
    }

    public function getAddressDeliveryCountry(): ?string
    {
        return $this->addressDeliveryCountry;
    }

    public function setAddressDeliveryCountry(?string $addressDeliveryCountry): self
    {
        $this->addressDeliveryCountry = $addressDeliveryCountry;

        return $this;
    }

    public function getAddressDeliveryStreet(): ?string
    {
        return $this->addressDeliveryStreet;
    }

    public function setAddressDeliveryStreet(?string $addressDeliveryStreet): self
    {
        $this->addressDeliveryStreet = $addressDeliveryStreet;

        return $this;
    }

    public function getAddressDeliveryTown(): ?string
    {
        return $this->addressDeliveryTown;
    }

    public function setAddressDeliveryTown(?string $addressDeliveryTown): self
    {
        $this->addressDeliveryTown = $addressDeliveryTown;

        return $this;
    }

    public function getAddressDeliveryZip(): ?string
    {
        return $this->addressDeliveryZip;
    }

    public function setAddressDeliveryZip(?string $addressDeliveryZip): self
    {
        $this->addressDeliveryZip = preg_replace('/\s+/', '', $addressDeliveryZip);

        return $this;
    }

    /**
     * @ORM\PreFlush
     */
    public function fixDeliveryMethodData(): void
    {
        /* Historická data pro doručovací metodu */
        if ($this->deliveryMethod === null)
        {
            $this->deliveryPriceWithoutVat = 0.0;
            $this->deliveryPriceWithVat = 0.0;
            $this->deliveryMethodName = null;

            if ($this->addressDeliveryLocked)
            {
                $this->resetAddressDelivery();
            }
        }
        else
        {
            $this->deliveryPriceWithoutVat = $this->deliveryMethod->getPriceWithoutVat();
            $this->deliveryPriceWithVat = $this->deliveryMethod->getPriceWithVat();
            $this->deliveryMethodName = $this->deliveryMethod->getName();
        }

        /* Zamykání/odemykání doručovací adresy */
        if ($this->deliveryMethod !== null && isset(self::DELIVERY_METHODS_THAT_LOCK_ADDRESS[$this->deliveryMethod->getType()]))
        {
            $this->addressDeliveryLocked = true;
        }
        else
        {
            $this->addressDeliveryLocked = false;
        }
    }

    /**
     * @ORM\PreFlush
     */
    public function fixPaymentMethodData(): void
    {
        /* Historická data pro platební metodu */
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
    }

    public function getStaticAddressDeliveryAdditionalInfo(): ?string
    {
        return $this->staticAddressDeliveryAdditionalInfo;
    }

    public function setStaticAddressDeliveryAdditionalInfo(?string $staticAddressDeliveryAdditionalInfo): self
    {
        $this->staticAddressDeliveryAdditionalInfo = $staticAddressDeliveryAdditionalInfo;

        return $this;
    }

    public function getStaticAddressDeliveryCountry(): ?string
    {
        return $this->staticAddressDeliveryCountry;
    }

    public function setStaticAddressDeliveryCountry(?string $staticAddressDeliveryCountry): self
    {
        $this->staticAddressDeliveryCountry = $staticAddressDeliveryCountry;

        return $this;
    }

    public function getStaticAddressDeliveryStreet(): ?string
    {
        return $this->staticAddressDeliveryStreet;
    }

    public function setStaticAddressDeliveryStreet(?string $staticAddressDeliveryStreet): self
    {
        $this->staticAddressDeliveryStreet = $staticAddressDeliveryStreet;

        return $this;
    }

    public function getStaticAddressDeliveryTown(): ?string
    {
        return $this->staticAddressDeliveryTown;
    }

    public function setStaticAddressDeliveryTown(?string $staticAddressDeliveryTown): self
    {
        $this->staticAddressDeliveryTown = $staticAddressDeliveryTown;

        return $this;
    }

    public function getStaticAddressDeliveryZip(): ?string
    {
        return $this->staticAddressDeliveryZip;
    }

    public function setStaticAddressDeliveryZip(?string $staticAddressDeliveryZip): self
    {
        $this->staticAddressDeliveryZip = $staticAddressDeliveryZip;

        return $this;
    }

    public function determinePreviousDeliveryType(): self
    {
        if($this->getDeliveryMethod() === null)
        {
            $this->previousDeliveryType = null;
        }
        else
        {
            $this->previousDeliveryType = $this->getDeliveryMethod()->getType();
        }

        return $this;
    }

    public function determineAddressDelivery(): self
    {
        // přechod na null/Českou poštu
        if ($this->addressDeliveryLocked && ($this->deliveryMethod === null || !isset(self::DELIVERY_METHODS_THAT_LOCK_ADDRESS[$this->deliveryMethod->getType()])))
        {
            $this->resetAddressDelivery();
        }

        // přechod na Zásilkovnu
        if ($this->staticAddressDeliveryAdditionalInfo !== null && $this->deliveryMethod !== null && isset(self::DELIVERY_METHODS_THAT_LOCK_ADDRESS[$this->deliveryMethod->getType()]))
        {
            $this->loadAddressDeliveryFromStatic();
        }

        return $this;
    }

    private function loadAddressDeliveryFromStatic(): void
    {
        $this->setAddressDeliveryAdditionalInfo($this->staticAddressDeliveryAdditionalInfo);
        $this->setAddressDeliveryCountry($this->staticAddressDeliveryCountry);
        $this->setAddressDeliveryStreet($this->staticAddressDeliveryStreet);
        $this->setAddressDeliveryTown($this->staticAddressDeliveryTown);
        $this->setAddressDeliveryZip($this->staticAddressDeliveryZip);
    }

    private function resetAddressDelivery(): void
    {
        $this->addressDeliveryAdditionalInfo = null;
        $this->addressDeliveryCountry = null;
        $this->addressDeliveryStreet = null;
        $this->addressDeliveryTown = null;
        $this->addressDeliveryZip = null;
    }
}