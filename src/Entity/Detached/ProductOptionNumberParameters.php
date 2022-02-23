<?php

namespace App\Entity\Detached;

use App\Validation\Compound as AssertCompound;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class ProductOptionNumberParameters
{
    /**
     * @AssertCompound\ProductOptionParameterNumberRequirements
     */
    private $min;

    /**
     * @AssertCompound\ProductOptionParameterNumberRequirements
     */
    private $max;

    /**
     * @AssertCompound\ProductOptionParameterNumberRequirements
     */
    private $default;

    /**
     * @AssertCompound\ProductOptionParameterNumberRequirements
     */
    private $step;

    /**
     * @Assert\Callback()
     */
    public function validate(ExecutionContextInterface $context)
    {
        if(is_numeric($this->min) && is_numeric($this->max))
        {
            if ($this->min >= $this->max)
            {
                $context->buildViolation('Minimální číslo musí být menší než maximální číslo.')
                    ->atPath('min')
                    ->addViolation();
            }

            if(is_numeric($this->default))
            {
                if($this->default < $this->min || $this->default > $this->max)
                {
                    $context->buildViolation('Výchozí číslo musí být mezi minimálním a maximálním číslem.')
                        ->atPath('default')
                        ->addViolation();
                }
            }
        }
    }

    public function getMin(): ?string
    {
        return $this->min;
    }

    public function setMin(?string $min): self
    {
        $this->min = $min;

        return $this;
    }

    public function getMax(): ?string
    {
        return $this->max;
    }

    public function setMax(?string $max): self
    {
        $this->max = $max;

        return $this;
    }

    public function getDefault(): ?string
    {
        return $this->default;
    }

    public function setDefault(?string $default): self
    {
        $this->default = $default;

        return $this;
    }

    public function getStep(): ?string
    {
        return $this->step;
    }

    public function setStep(?string $step): self
    {
        $this->step = $step;

        return $this;
    }
}
