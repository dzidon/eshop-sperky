<?php

namespace App\Validation;

use App\Entity\CartOccurence;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Constraint;

class CartOccurenceQuantityValidator extends ConstraintValidator
{
    public function validate($collection, Constraint $constraint)
    {
        if (!$constraint instanceof CartOccurenceQuantity)
        {
            throw new UnexpectedTypeException($constraint, CartOccurenceQuantity::class);
        }

        if (!$collection instanceof Collection)
        {
            return;
        }

        $productsTotal = [];

        /** @var CartOccurence $cartOccurence */
        foreach ($collection as $key => $cartOccurence)
        {
            $product = $cartOccurence->getProduct();

            if($product !== null)
            {
                if (!isset($productsTotal[$product->getId()]))
                {
                    $productsTotal[$product->getId()] = $cartOccurence->getQuantity();
                }
                else
                {
                    $productsTotal[$product->getId()] += $cartOccurence->getQuantity();
                }

                if ($productsTotal[$product->getId()] > $product->getInventory())
                {
                    $this->context
                        ->buildViolation($constraint->message)
                        ->setParameter('{{ inventory }}', $product->getInventory())
                        ->setParameter('{{ productName }}', $product->getName())
                        ->atPath(sprintf('[%s].quantity', $key))
                        ->addViolation();
                }
            }
        }
    }
}