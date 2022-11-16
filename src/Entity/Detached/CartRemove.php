<?php

namespace App\Entity\Detached;

use App\Entity\CartOccurence;
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
    private $cartOccurence;

    public function getCartOccurence(): ?CartOccurence
    {
        return $this->cartOccurence;
    }

    public function setCartOccurence(?CartOccurence $cartOccurence): self
    {
        $this->cartOccurence = $cartOccurence;

        return $this;
    }
}