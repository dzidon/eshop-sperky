<?php

namespace App\Validation;

use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;
use Symfony\Component\Validator\Constraint;

class ZipCodeValidator extends ConstraintValidator
{
    public function validate($value, Constraint $constraint)
    {
        if (!$constraint instanceof ZipCode)
        {
            throw new UnexpectedTypeException($constraint, ZipCode::class);
        }

        if ($value === null || $value === '')
        {
            return;
        }

        if (!is_string($value))
        {
            throw new UnexpectedValueException($value, 'string');
        }

        if (!preg_match('/^\d{5}$/', $value))
        {
            $this->context
                ->buildViolation($constraint->message)
                ->addViolation();
        }
    }
}