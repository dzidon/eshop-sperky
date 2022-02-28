<?php

namespace App\Validation;

use Doctrine\Common\Collections\Collection;
use LogicException;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Constraint;

class UniqueEntitiesInCollectionValidator extends ConstraintValidator
{
    public function validate($protocol, Constraint $constraint)
    {
        if (!$constraint instanceof UniqueEntitiesInCollection)
        {
            throw new UnexpectedTypeException($constraint, UniqueEntitiesInCollection::class);
        }

        $fieldsOfChildren = $constraint->fieldsOfChildren;
        $collectionName = $constraint->collectionName;

        if ($fieldsOfChildren === null || $collectionName === null)
        {
            throw new LogicException('V UniqueEntitiesInCollection chybí některý z následujících options: fieldsOfChildren (array), collectionName (string).');
        }

        if (!is_array($fieldsOfChildren) || !is_string($collectionName))
        {
            throw new LogicException('Některý z options v UniqueEntitiesInCollection má nesprávný typ. Povolené typy pro options: fieldsOfChildren (array), collectionName (string).');
        }

        $getCollection = 'get' . ucfirst($collectionName);
        $collection = $protocol->$getCollection();

        if (!$collection instanceof Collection)
        {
            return;
        }

        $occurencesForGetters = [];
        foreach ($fieldsOfChildren as $field)
        {
            $getter = 'get' . ucfirst($field);
            $occurencesForGetters[$getter] = [];
        }

        foreach ($collection as $element)
        {
            foreach ($occurencesForGetters as $getValue => $occurences)
            {
                $value = $element->$getValue();
                if($value === null)
                {
                    continue;
                }

                if (!isset($occurencesForGetters[$getValue][$value]))
                {
                    $occurencesForGetters[$getValue][$value] = 1;
                }
                else
                {
                    $occurencesForGetters[$getValue][$value] ++;
                }

                if ($occurencesForGetters[$getValue][$value] >= 2)
                {
                    $this->context
                        ->buildViolation($constraint->message)
                        ->atPath($collectionName)
                        ->addViolation();

                    return;
                }
            }
        }
    }
}