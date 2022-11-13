<?php

namespace App\Entity\Detached;

use App\Validation\Compound as AssertCompound;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Třída představující akci odstranění produktu z košíku.
 *
 * @package App\Entity\Detached
 */
class CartRemove
{
    /**
     * @Assert\NotNull(message="Produkt je neplatný.")
     */
    private $cartOccurenceId;

    public function getCartOccurenceId(): ?int
    {
        return $this->cartOccurenceId;
    }

    public function setCartOccurenceId(?int $cartOccurenceId): self
    {
        $this->cartOccurenceId = $cartOccurenceId;

        return $this;
    }
}