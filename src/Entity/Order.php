<?php

namespace App\Entity;

use DateTime;
use DateTimeInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Uid\Uuid;
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
}