<?php


namespace App\Validation;

use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Constraint;

class AllOrNoneValidator extends ConstraintValidator
{
    public function validate($protocol, Constraint $constraint)
    {
        if (!$constraint instanceof AllOrNone)
        {
            throw new UnexpectedTypeException($constraint, AllOrNone::class);
        }

        $isNull = [];
        foreach ($constraint->targetAttributes as $attribute) //loop přes např. ["company", "ic", "dic"]
        {
            $isNull[$attribute] = false;

            $getter = 'get' . ucfirst($attribute);  //např. getCompany
            if($protocol->$getter() === null || mb_strlen($protocol->$getter(), 'utf-8') === 0)
            {
                $isNull[$attribute] = true;
            }
        }

        if(count(array_unique($isNull)) === 2) //v $isNull je alespon jedno true a alespon jedno false
        {
            foreach ($isNull as $attributeName => $showError)
            {
                if($showError)
                {
                    $this->context->buildViolation($constraint->message)
                        ->atPath($attributeName)
                        ->addViolation();
                }
            }
        }
    }
}