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

        if (!preg_match('/^\d{8}$/', $value))
        {
            $this->context
                ->buildViolation($constraint->message)
                ->addViolation();
        }
    }
}