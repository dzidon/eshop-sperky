<?php

namespace App\Validation;

use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

class VatValidator extends ConstraintValidator
{
    private ParameterBagInterface $parameterBag;

    public function __construct(ParameterBagInterface $parameterBag)
    {
        $this->parameterBag = $parameterBag;
    }

    public function validate($value, Constraint $constraint)
    {
        if (!$constraint instanceof Vat)
        {
            throw new UnexpectedTypeException($constraint, Vat::class);
        }

        if ($value === null || $value === '')
        {
            return;
        }

        if (!is_numeric($value))
        {
            throw new UnexpectedValueException($value, 'numeric');
        }

        $isVatPayer = $this->parameterBag->get('app_vat_payer');
        if(!$isVatPayer && $value > 0)
        {
            $this->context
                ->buildViolation($constraint->message)
                ->addViolation();
        }
    }
}