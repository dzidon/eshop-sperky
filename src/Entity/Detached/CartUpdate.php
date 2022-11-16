<?php

namespace App\Entity\Detached;

use App\Entity\CartOccurence;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Validator\Constraints as Assert;
use App\Validation as AssertCustom;

/**
 * Třída představující akci aktualizace obsahu celého košíku.
 *
 * @package App\Entity\Detached
 */
class CartUpdate
{
    /**
     * @AssertCustom\CartOccurenceQuantity()
     * @Assert\Valid()
     */
    private $cartOccurences;

    public function __construct()
    {
        $this->cartOccurences = new ArrayCollection();
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
        if (!$this->cartOccurences->contains($cartOccurence))
        {
            $this->cartOccurences[] = $cartOccurence;
        }

        return $this;
    }

    public function removeCartOccurence(CartOccurence $cartOccurence): self
    {
        $this->cartOccurences->removeElement($cartOccurence);

        return $this;
    }
}