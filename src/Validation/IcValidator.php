<?php

namespace App\Validation;

use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;
use Symfony\Component\Validator\Constraint;

class IcValidator extends ConstraintValidator
{
    public function validate($value, Constraint $constraint)
    {
        if (!$constraint instanceof Ic)
        {
            throw new UnexpectedTypeException($constraint, Ic::class);
        }

        if ($value === null || $value === '')
        {
            return;
        }

        if (!is_string($value))
        {
            throw new UnexpectedValueException($value, 'string');
        }

        if (!$this->isValidIc($value))
        {
            $this->context
                ->buildViolation($constraint->message)
                ->addViolation();
        }
    }

    public function isValidIc(string $ic): bool
    {
        // 8 číslic
        if (!preg_match('#^\d{8}$#', $ic))
        {
            return false;
        }

        // kontrolní součet
        $a = 0;
        for ($i = 0; $i < 7; $i++)
        {
            $a += $ic[$i] * (8 - $i);
        }

        $a = $a % 11;
        if ($a === 0)
        {
            $c = 1;
        }
        elseif ($a === 1)
        {
            $c = 0;
        }
        else
        {
            $c = 11 - $a;
        }

        return (int) $ic[7] === $c;
    }
}