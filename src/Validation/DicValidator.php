<?php

namespace App\Validation;

use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;
use Symfony\Component\Validator\Constraint;

class DicValidator extends ConstraintValidator
{
    public function validate($value, Constraint $constraint)
    {
        if (!$constraint instanceof Dic)
        {
            throw new UnexpectedTypeException($constraint, Dic::class);
        }

        if ($value === null || $value === '')
        {
            return;
        }

        if (!is_string($value))
        {
            throw new UnexpectedValueException($value, 'string');
        }

        if (!preg_match('/^((CZ|SK)(\d{8,10}))?$/', $value))
        {
            $this->context
                ->buildViolation($constraint->message)
                ->addViolation();
        }
    }
}