<?php

namespace App\Entity;

use DateTime;
use DateTimeInterface;
use Doctrine\ORM\Mapping as ORM;
use App\Repository\PaymentRepository;

/**
 * @ORM\Entity(repositoryClass=PaymentRepository::class)
 */
class Payment
{
    public const STATE_CREATED = 'CREATED';
    public const STATE_PAID = 'PAID';
    public const STATE_CANCELED = 'CANCELED';
    public const STATE_PAYMENT_METHOD_CHOSEN = 'PAYMENT_METHOD_CHOSEN';
    public const STATE_TIMEOUTED = 'TIMEOUTED';
    public const STATE_AUTHORIZED = 'AUTHORIZED';
    public const STATE_REFUNDED = 'REFUNDED';
    public const STATE_PARTIALLY_REFUNDED = 'PARTIALLY_REFUNDED';

    public const VALID_STATE_CHANGES = [
        self::STATE_CREATED => [
            self::STATE_TIMEOUTED, self::STATE_PAYMENT_METHOD_CHOSEN
        ],
        self::STATE_PAYMENT_METHOD_CHOSEN => [
            self::STATE_PAID, self::STATE_CANCELED, self::STATE_TIMEOUTED
        ],
        self::STATE_PAID => [
            self::STATE_REFUNDED, self::STATE_PARTIALLY_REFUNDED
        ],
    ];

    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="integer")
     */
    private int $externalId;

    /**
     * @ORM\Column(type="string", length=32)
     */
    private string $state;

    /**
     * @ORM\ManyToOne(targetEntity=Order::class, inversedBy="payments")
     * @ORM\JoinColumn(nullable=false, onDelete="CASCADE")
     */
    private Order $order_;

    /**
     * @ORM\Column(type="datetime")
     */
    private $created;

    /**
     * @ORM\Column(type="datetime")
     */
    private $updated;

    private $gateUrl;

    public function __construct(int $externalId, string $state, Order $order, string $gateUrl = null)
    {
        $this->externalId = $externalId;
        $this->state = $state;
        $this->gateUrl = $gateUrl;
        $order->addPayment($this);

        $this->created = new DateTime('now');
        $this->updated = $this->created;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getExternalId(): int
    {
        return $this->externalId;
    }

    public function setExternalId(int $externalId): self
    {
        $this->externalId = $externalId;

        return $this;
    }

    public function getState(): string
    {
        return $this->state;
    }

    public function setState(string $state): self
    {
        $this->state = $state;

        return $this;
    }

    public function getOrder(): Order
    {
        return $this->order_;
    }

    public function setOrder(Order $order_): self
    {
        $this->order_ = $order_;

        return $this;
    }

    public function getGateUrl(): ?string
    {
        return $this->gateUrl;
    }

    public function setGateUrl(string $gateUrl): self
    {
        $this->gateUrl = $gateUrl;

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

    public function stateChangeIsValid(string $newState): bool
    {
        $oldState = $this->state;

        if (isset(self::VALID_STATE_CHANGES[$oldState]))
        {
            if (in_array($newState, self::VALID_STATE_CHANGES[$oldState]))
            {
                return true;
            }
        }

        return false;
    }
}
