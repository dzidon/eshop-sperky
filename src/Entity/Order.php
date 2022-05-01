<?php

namespace App\Entity;

use App\Entity\Detached\Search\SearchAndSort;
use DateTime;
use DateTimeInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use LogicException;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;
use App\Validation\Compound as AssertCompound;
use App\Validation as AssertCustom;
use App\Repository\OrderRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=OrderRepository::class)
 * @ORM\Table(name="order_")
 * @ORM\HasLifecycleCallbacks()
 *
 * @AssertCustom\PacketaId(groups={"methods"})
 * @AssertCustom\PacketaExists(groups={"admin_state"})
 */
class Order
{
    public const LIFETIME_IN_DAYS = 60;
    public const REFRESH_WINDOW_IN_DAYS = 30;

    public const LIFECYCLE_FRESH = 0;
    public const LIFECYCLE_AWAITING_PAYMENT = 1;
    public const LIFECYCLE_AWAITING_SHIPPING = 2;
    public const LIFECYCLE_SHIPPED = 3;
    public const LIFECYCLE_CANCELLED = 4;

    public const LIFECYCLE_CHAPTERS = [
        self::LIFECYCLE_FRESH => 'Neúplná',
        self::LIFECYCLE_AWAITING_PAYMENT => 'Čeká na zaplacení',
        self::LIFECYCLE_AWAITING_SHIPPING => 'Čeká na odeslání',
        self::LIFECYCLE_SHIPPED => 'Odeslaná',
        self::LIFECYCLE_CANCELLED => 'Zrušená',
    ];

    public const LIFECYCLE_CHAPTERS_ADMIN_EDIT = [
        self::LIFECYCLE_CHAPTERS[self::LIFECYCLE_AWAITING_PAYMENT] => self::LIFECYCLE_AWAITING_PAYMENT,
        self::LIFECYCLE_CHAPTERS[self::LIFECYCLE_AWAITING_SHIPPING] => self::LIFECYCLE_AWAITING_SHIPPING,
        self::LIFECYCLE_CHAPTERS[self::LIFECYCLE_SHIPPED] => self::LIFECYCLE_SHIPPED,
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
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $expireAt;

    /**
     * @ORM\OneToMany(targetEntity=CartOccurence::class, mappedBy="order_", cascade={"persist"})
     *
     * @AssertCustom\CartOccurenceQuantity(groups={"cart"})
     * @Assert\Valid(groups={"cart", "onDemandCreation"})
     * @Assert\Count(min=1, groups={"addresses", "onDemandCreation"}, minMessage="Objednávka musí mít alespoň 1 produkt.")
     */
    private $cartOccurences;

    /**
     * @ORM\Column(type="boolean")
     */
    private bool $createdManually = false;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $cashOnDelivery = 0.0;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $finishedAt;

    /**
     * @ORM\Column(type="integer")
     *
     * @Assert\Choice(choices=Order::LIFECYCLE_CHAPTERS_ADMIN_EDIT, groups={"admin_state"}, message="Zvolte platný stav.")
     * @Assert\NotBlank(groups={"admin_state"})
     */
    private int $lifecycleChapter = self::LIFECYCLE_FRESH;

    /**
     * @ORM\ManyToOne(targetEntity=DeliveryMethod::class, inversedBy="orders")
     * @ORM\JoinColumn(onDelete="SET NULL")
     *
     * @Assert\NotBlank(groups={"addresses"}, message="Vraťte se na předchozí krok a vyberte způsob doručení.")
     */
    private $deliveryMethod;

    /**
     * @ORM\ManyToOne(targetEntity=PaymentMethod::class, inversedBy="orders")
     * @ORM\JoinColumn(onDelete="SET NULL")
     *
     * @Assert\NotBlank(groups={"addresses"}, message="Vraťte se na předchozí krok a vyberte způsob platby.")
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
     * Osobní údaje
     */

    /**
     * @ORM\Column(type="string", length=180, nullable=true)
     *
     * @AssertCompound\EmailRequirements(groups={"addresses"})
     * @Assert\NotBlank(groups={"addresses"})
     */
    private $email;

    /**
     * @ORM\Column(type="phone_number", nullable=true)
     *
     * @Assert\NotBlank(groups={"addresses"})
     */
    private $phoneNumber;

    /*
     * Doručovací adresa
     */

    /**
     * @ORM\Column(type="boolean")
     */
    private $addressDeliveryLocked = false;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     *
     * @Assert\Length(max=255, groups={"addresses"}, maxMessage="Maximální počet znaků v křestním jméně: {{ limit }}")
     * @Assert\NotBlank(groups={"addresses"})
     */
    private $addressDeliveryNameFirst;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     *
     * @Assert\Length(max=255, groups={"addresses"}, maxMessage="Maximální počet znaků v příjmení: {{ limit }}")
     * @Assert\NotBlank(groups={"addresses"})
     */
    private $addressDeliveryNameLast;

    /**
     * @ORM\Column(type="string", length=32, nullable=true)
     *
     * @Assert\Choice(choices=Address::COUNTRY_NAMES, groups={"addresses_delivery"}, message="Zvolte platnou zemi.")
     * @Assert\NotBlank(groups={"addresses_delivery"})
     */
    private $addressDeliveryCountry;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     *
     * @AssertCustom\Compound\StreetRequirements(groups={"addresses_delivery"})
     * @Assert\NotBlank(groups={"addresses_delivery"})
     */
    private $addressDeliveryStreet;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     *
     * @Assert\Length(max=255, groups={"addresses_delivery"}, maxMessage="Maximální počet znaků v obci: {{ limit }}")
     * @Assert\NotBlank(groups={"addresses_delivery"})
     */
    private $addressDeliveryTown;

    /**
     * @ORM\Column(type="string", length=5, nullable=true)
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

    /*
     * Firma
     */

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     *
     * @Assert\Length(max=255, groups={"addresses_company"}, maxMessage="Maximální počet znaků v názvu firmy: {{ limit }}")
     */
    private $addressBillingCompany;

    /**
     * @ORM\Column(type="string", length=8, nullable=true)
     *
     * @AssertCustom\Ic(groups={"addresses_company"})
     * @Assert\NotBlank(groups={"addresses_company"})
     */
    private $addressBillingIc;

    /**
     * @ORM\Column(type="string", length=12, nullable=true)
     *
     * @AssertCustom\Dic(groups={"addresses_company"})
     * @Assert\NotBlank(groups={"addresses_company"})
     */
    private $addressBillingDic;

    /*
     * Fakturacni adresa
     */

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     *
     * @Assert\Length(max=255, groups={"addresses_billing"}, maxMessage="Maximální počet znaků v křestním jméně: {{ limit }}")
     * @Assert\NotBlank(groups={"addresses_billing"})
     */
    private $addressBillingNameFirst;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     *
     * @Assert\Length(max=255, groups={"addresses_billing"}, maxMessage="Maximální počet znaků v příjmení: {{ limit }}")
     * @Assert\NotBlank(groups={"addresses_billing"})
     */
    private $addressBillingNameLast;

    /**
     * @ORM\Column(type="string", length=32, nullable=true)
     *
     * @Assert\Choice(choices=Address::COUNTRY_NAMES, groups={"addresses_billing"}, message="Zvolte platnou zemi.")
     * @Assert\NotBlank(groups={"addresses_billing"})
     */
    private $addressBillingCountry;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     *
     * @AssertCustom\Compound\StreetRequirements(groups={"addresses_billing"})
     * @Assert\NotBlank(groups={"addresses_billing"})
     */
    private $addressBillingStreet;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     *
     * @Assert\Length(max=255, groups={"addresses_billing"}, maxMessage="Maximální počet znaků v doplňku adresy: {{ limit }}")
     */
    private $addressBillingAdditionalInfo;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     *
     * @Assert\Length(max=255, groups={"addresses_billing"}, maxMessage="Maximální počet znaků v obci: {{ limit }}")
     * @Assert\NotBlank(groups={"addresses_billing"})
     */
    private $addressBillingTown;

    /**
     * @ORM\Column(type="string", length=5, nullable=true)
     *
     * @AssertCustom\ZipCode(groups={"addresses_billing"})
     * @Assert\NotBlank(groups={"addresses_billing"})
     */
    private $addressBillingZip;

    /*
     * Ostatní
     */

    /**
     * @ORM\Column(type="string", length=500, nullable=true)
     *
     * @Assert\Length(max=500, groups={"addresses_note"}, maxMessage="Maximální počet znaků v poznámce: {{ limit }}")
     * @Assert\NotBlank(groups={"addresses_note"})
     */
    private $note;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     *
     * @Assert\Length(max=255, groups={"cancellation"}, maxMessage="Maximální počet znaků v důvodu zrušení: {{ limit }}")
     * @Assert\NotBlank(groups={"cancellation"})
     */
    private $cancellationReason;

    /**
     * @ORM\ManyToOne(targetEntity=User::class)
     * @ORM\JoinColumn(nullable=true, onDelete="SET NULL")
     */
    private $user;

    /**
     * @ORM\Column(type="datetime")
     */
    private $created;

    /**
     * @ORM\Column(type="datetime")
     */
    private $updated;

    private $cartOccurencesWithProduct;

    /**
     * @Assert\Type("numeric", groups={"admin_packeta"}, message="Musíte zadat číselnou hodnotu.")
     * @Assert\GreaterThan(value=0, groups={"admin_packeta"})
     * @Assert\NotBlank(groups={"admin_packeta"})
     */
    private $weight;

    private bool $companyChecked = false;
    private bool $billingAddressChecked = false;
    private bool $noteChecked = false;

    private $staticAddressDeliveryCountry;
    private $staticAddressDeliveryStreet;
    private $staticAddressDeliveryTown;
    private $staticAddressDeliveryZip;
    private $staticAddressDeliveryAdditionalInfo;

    private int $totalQuantity = 0;
    private float $totalPriceWithoutVat = 0.0;
    private float $totalPriceWithVat = 0.0;

    private bool $hasSynchronizationWarnings = false;

    public function __construct()
    {
        $this->token = Uuid::v4();
        $this->cartOccurences = new ArrayCollection();

        $this->created = new DateTime('now');
        $this->updated = $this->created;
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

    public function setExpireAt(?DateTimeInterface $expireAt): self
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

    public function setCartOccurencesWithProduct(?Collection $cartOccurencesWithProduct): self
    {
        $this->cartOccurencesWithProduct = $cartOccurencesWithProduct;

        return $this;
    }

    /**
     * @param bool $forceReload
     * @return Collection|CartOccurence[]
     */
    public function getCartOccurencesWithProduct(bool $forceReload = false): Collection
    {
        if ($this->cartOccurencesWithProduct === null || $forceReload)
        {
            $this->cartOccurencesWithProduct = new ArrayCollection();

            /** @var CartOccurence $cartOccurence */
            foreach ($this->cartOccurences as $cartOccurence)
            {
                if ($cartOccurence->getProduct() !== null)
                {
                    $this->cartOccurencesWithProduct->add($cartOccurence);
                }
            }
        }

        return $this->cartOccurencesWithProduct;
    }

    public function getWeight(): ?float
    {
        return $this->weight;
    }

    public function setWeight(?float $weight): self
    {
        $this->weight = $weight;

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

    public function getTypeName(): string
    {
        if ($this->createdManually)
        {
            return 'Vytvořená na míru';
        }
        else
        {
            return 'Vytvořená z katalogu';
        }
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

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): self
    {
        $this->email = $email;

        return $this;
    }

    public function getPhoneNumber()
    {
        return $this->phoneNumber;
    }

    public function setPhoneNumber($phoneNumber): self
    {
        $this->phoneNumber = $phoneNumber;

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

    public function getAddressDeliveryNameFirst(): ?string
    {
        return $this->addressDeliveryNameFirst;
    }

    public function setAddressDeliveryNameFirst(?string $addressDeliveryNameFirst): self
    {
        $this->addressDeliveryNameFirst = $addressDeliveryNameFirst;

        return $this;
    }

    public function getAddressDeliveryNameLast(): ?string
    {
        return $this->addressDeliveryNameLast;
    }

    public function setAddressDeliveryNameLast(?string $addressDeliveryNameLast): self
    {
        $this->addressDeliveryNameLast = $addressDeliveryNameLast;

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
        $this->addressDeliveryZip = $addressDeliveryZip;
        if ($this->addressDeliveryZip !== null)
        {
            $this->addressDeliveryZip = preg_replace('/\s+/', '', $this->addressDeliveryZip);
        }

        return $this;
    }

    public function getAddressBillingCompany(): ?string
    {
        return $this->addressBillingCompany;
    }

    public function setAddressBillingCompany(?string $addressBillingCompany): self
    {
        $this->addressBillingCompany = $addressBillingCompany;

        return $this;
    }

    public function getAddressBillingIc(): ?string
    {
        return $this->addressBillingIc;
    }

    public function setAddressBillingIc(?string $addressBillingIc): self
    {
        $this->addressBillingIc = $addressBillingIc;

        return $this;
    }

    public function getAddressBillingDic(): ?string
    {
        return $this->addressBillingDic;
    }

    public function setAddressBillingDic(?string $addressBillingDic): self
    {
        $this->addressBillingDic = $addressBillingDic;

        return $this;
    }

    public function getAddressBillingNameFirst(): ?string
    {
        return $this->addressBillingNameFirst;
    }

    public function setAddressBillingNameFirst(?string $addressBillingNameFirst): self
    {
        $this->addressBillingNameFirst = $addressBillingNameFirst;

        return $this;
    }

    public function getAddressBillingNameLast(): ?string
    {
        return $this->addressBillingNameLast;
    }

    public function setAddressBillingNameLast(?string $addressBillingNameLast): self
    {
        $this->addressBillingNameLast = $addressBillingNameLast;

        return $this;
    }

    public function getAddressBillingCountry(): ?string
    {
        return $this->addressBillingCountry;
    }

    public function setAddressBillingCountry(?string $addressBillingCountry): self
    {
        $this->addressBillingCountry = $addressBillingCountry;

        return $this;
    }

    public function getAddressBillingStreet(): ?string
    {
        return $this->addressBillingStreet;
    }

    public function setAddressBillingStreet(?string $addressBillingStreet): self
    {
        $this->addressBillingStreet = $addressBillingStreet;

        return $this;
    }

    public function getAddressBillingAdditionalInfo(): ?string
    {
        return $this->addressBillingAdditionalInfo;
    }

    public function setAddressBillingAdditionalInfo(?string $addressBillingAdditionalInfo): self
    {
        $this->addressBillingAdditionalInfo = $addressBillingAdditionalInfo;

        return $this;
    }

    public function getAddressBillingTown(): ?string
    {
        return $this->addressBillingTown;
    }

    public function setAddressBillingTown(?string $addressBillingTown): self
    {
        $this->addressBillingTown = $addressBillingTown;

        return $this;
    }

    public function getAddressBillingZip(): ?string
    {
        return $this->addressBillingZip;
    }

    public function setAddressBillingZip(?string $addressBillingZip): self
    {
        $this->addressBillingZip = preg_replace('/\s+/', '', $addressBillingZip);

        return $this;
    }

    public function getNote(): ?string
    {
        return $this->note;
    }

    public function setNote(?string $note): self
    {
        $this->note = $note;

        return $this;
    }

    public function getCancellationReason(): ?string
    {
        return $this->cancellationReason;
    }

    public function setCancellationReason(?string $cancellationReason): self
    {
        $this->cancellationReason = $cancellationReason;

        return $this;
    }

    public function getCashOnDelivery(): ?float
    {
        return $this->cashOnDelivery;
    }

    public function setCashOnDelivery(float $cashOnDelivery): self
    {
        $this->cashOnDelivery = $cashOnDelivery;

        return $this;
    }

    public function getFinishedAt(): ?DateTimeInterface
    {
        return $this->finishedAt;
    }

    public function setFinishedAt(DateTimeInterface $finishedAt): self
    {
        $this->finishedAt = $finishedAt;

        return $this;
    }

    public function isFresh(): bool
    {
        return $this->lifecycleChapter === self::LIFECYCLE_FRESH;
    }

    public function isCancelled(): bool
    {
        return $this->lifecycleChapter === self::LIFECYCLE_CANCELLED;
    }

    public function getLifecycleChapter(): int
    {
        return $this->lifecycleChapter;
    }

    public function getLifecycleChapterName(): string
    {
        return self::LIFECYCLE_CHAPTERS[$this->lifecycleChapter];
    }

    public function setLifecycleChapter(int $lifecycleChapter): self
    {
        if (!isset(self::LIFECYCLE_CHAPTERS[$lifecycleChapter]))
        {
            throw new LogicException(sprintf('Objednávce (App\Entity\Order) nejde nastavit lifecycleChapter %d.', $lifecycleChapter));
        }
        $this->lifecycleChapter = $lifecycleChapter;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;

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

    public function finish(?User $user): void
    {
        $this->setLifecycleChapter(self::LIFECYCLE_AWAITING_PAYMENT);

        // nastavení částky dobírky + objednávka na dobírku bude rovnou připravená na odeslání
        if ($this->paymentMethod !== null && $this->paymentMethod->getType() === PaymentMethod::TYPE_ON_DELIVERY)
        {
            $cashOnDelivery = $this->getTotalPriceWithVat($withMethods = true);
            $this->setCashOnDelivery(ceil($cashOnDelivery));
            $this->setLifecycleChapter(self::LIFECYCLE_AWAITING_SHIPPING);
        }

        // nezaškrtl, že chce zadat jinou fakturační adresu, takže se nastaví na hodnoty doručovací
        if (!$this->billingAddressChecked)
        {
            $this->loadAddressBillingFromDelivery();
        }

        // odečtení počtu produktů na skladě
        /** @var CartOccurence $cartOccurence */
        foreach ($this->cartOccurences as $cartOccurence)
        {
            $product = $cartOccurence->getProduct();
            $productInventory = $product->getInventory();
            $cartOccurenceQuantity = $cartOccurence->getQuantity();
            $product->setInventory($productInventory - $cartOccurenceQuantity);
        }

        $this->user = $user;
        $this->token = Uuid::v4();
        $this->finishedAt = new DateTime('now');
    }

    public function cancel(bool $forceInventoryReplenish): void
    {
        $this->setLifecycleChapter(self::LIFECYCLE_CANCELLED);

        // přidání počtu produktů zpět na sklad
        /** @var CartOccurence $cartOccurence */
        foreach ($this->cartOccurences as $cartOccurence)
        {
            if ($forceInventoryReplenish || $cartOccurence->isMarkedForInventoryReplenishment())
            {
                $product = $cartOccurence->getProduct();
                $productInventory = $product->getInventory();
                $cartOccurenceQuantity = $cartOccurence->getQuantity();
                $product->setInventory($productInventory + $cartOccurenceQuantity);
            }
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

    public function isCompanyChecked(): bool
    {
        return $this->companyChecked;
    }

    public function setCompanyChecked(bool $companyChecked): self
    {
        $this->companyChecked = $companyChecked;

        return $this;
    }

    public function isBillingAddressChecked(): bool
    {
        return $this->billingAddressChecked;
    }

    public function setBillingAddressChecked(bool $billingAddressChecked): self
    {
        $this->billingAddressChecked = $billingAddressChecked;

        return $this;
    }

    public function isNoteChecked(): bool
    {
        return $this->noteChecked;
    }

    public function setNoteChecked(bool $noteChecked): self
    {
        $this->noteChecked = $noteChecked;

        return $this;
    }

    public function hasSynchronizationWarnings(): bool
    {
        return $this->hasSynchronizationWarnings;
    }

    public function setHasSynchronizationWarnings(bool $hasSynchronizationWarnings): self
    {
        $this->hasSynchronizationWarnings = $hasSynchronizationWarnings;

        return $this;
    }

    public function deliveryMethodLocksDeliveryAddress(): bool
    {
        return ($this->deliveryMethod !== null && $this->deliveryMethod->locksDeliveryAddress());
    }

    public function addressesAreEqual(): bool
    {
        return
            $this->addressBillingCompany === null &&
            $this->addressBillingIc      === null &&
            $this->addressBillingDic     === null &&
            $this->addressDeliveryAdditionalInfo === $this->addressBillingAdditionalInfo &&
            $this->addressDeliveryNameFirst      === $this->addressBillingNameFirst &&
            $this->addressDeliveryNameLast       === $this->addressBillingNameLast &&
            $this->addressDeliveryCountry        === $this->addressBillingCountry &&
            $this->addressDeliveryStreet         === $this->addressBillingStreet &&
            $this->addressDeliveryTown           === $this->addressBillingTown &&
            $this->addressDeliveryZip            === $this->addressBillingZip
        ;
    }

    public function saveHistoricalDataForMethods(): void
    {
        $this->saveHistoricalDataForDeliveryMethod();
        $this->saveHistoricalDataForPaymentMethod();
    }

    public function injectAddressDeliveryToStatic(): void
    {
        $this->setStaticAddressDeliveryAdditionalInfo($this->addressDeliveryAdditionalInfo);
        $this->setStaticAddressDeliveryCountry($this->addressDeliveryCountry);
        $this->setStaticAddressDeliveryStreet($this->addressDeliveryStreet);
        $this->setStaticAddressDeliveryTown($this->addressDeliveryTown);
        $this->setStaticAddressDeliveryZip($this->addressDeliveryZip);
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
        $this->setAddressDeliveryAdditionalInfo(null);
        $this->setAddressDeliveryCountry(null);
        $this->setAddressDeliveryStreet(null);
        $this->setAddressDeliveryTown(null);
        $this->setAddressDeliveryZip(null);
    }

    private function loadAddressBillingFromDelivery(): void
    {
        $this->setAddressBillingAdditionalInfo($this->addressDeliveryAdditionalInfo);
        $this->setAddressBillingNameFirst($this->addressDeliveryNameFirst);
        $this->setAddressBillingNameLast($this->addressDeliveryNameLast);
        $this->setAddressBillingCountry($this->addressDeliveryCountry);
        $this->setAddressBillingStreet($this->addressDeliveryStreet);
        $this->setAddressBillingTown($this->addressDeliveryTown);
        $this->setAddressBillingZip($this->addressDeliveryZip);
    }

    public function resetAddressBilling(): void
    {
        $this->setAddressBillingNameFirst(null);
        $this->setAddressBillingNameLast(null);
        $this->setAddressBillingCountry(null);
        $this->setAddressBillingStreet(null);
        $this->setAddressBillingAdditionalInfo(null);
        $this->setAddressBillingTown(null);
        $this->setAddressBillingZip(null);
    }

    public function resetDataCompany(): void
    {
        $this->setAddressBillingCompany(null);
        $this->setAddressBillingIc(null);
        $this->setAddressBillingDic(null);
    }

    public function saveHistoricalDataForDeliveryMethod(): void
    {
        if ($this->deliveryMethod === null)
        {
            $this->deliveryPriceWithoutVat = 0.0;
            $this->deliveryPriceWithVat = 0.0;
            $this->deliveryMethodName = null;

            // nemá doručovací metodu a adresa je zamčená
            if ($this->addressDeliveryLocked)
            {
                $this->addressDeliveryLocked = false;
                $this->resetAddressDelivery();
            }
        }
        else
        {
            $this->deliveryPriceWithoutVat = $this->deliveryMethod->getPriceWithoutVat();
            $this->deliveryPriceWithVat = $this->deliveryMethod->getPriceWithVat();
            $this->deliveryMethodName = $this->deliveryMethod->getName();

            // má nastavenou doručovací metodu, která zamyká adresu (Zásilkovna)
            if ($this->deliveryMethodLocksDeliveryAddress())
            {
                $this->addressDeliveryLocked = true;

                // zrovna vybral nové výdejní místo
                if ($this->staticAddressDeliveryAdditionalInfo !== null)
                {
                    $this->loadAddressDeliveryFromStatic();
                }
            }
            // má nastavenou doručovací metodu, která nezamyká adresu (Česká pošta)
            else
            {
                if ($this->addressDeliveryLocked)
                {
                    $this->resetAddressDelivery();
                }

                $this->addressDeliveryLocked = false;
            }
        }
    }

    public function saveHistoricalDataForPaymentMethod(): void
    {
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

    /**
     * @ORM\PreUpdate
     */
    public function setUpdatedNow(): void
    {
        $this->updated = new DateTime('now');
    }

    public static function getSortData(): array
    {
        return [
            'ID (sestupně)' => 'id'.SearchAndSort::ATTRIBUTE_TAG_DESC,
            'ID (vzestupně)' => 'id'.SearchAndSort::ATTRIBUTE_TAG_ASC,
            'Od nejdříve dokončených' => 'finishedAt'.SearchAndSort::ATTRIBUTE_TAG_ASC,
            'Od naposledy dokončených' => 'finishedAt'.SearchAndSort::ATTRIBUTE_TAG_DESC,
        ];
    }
}