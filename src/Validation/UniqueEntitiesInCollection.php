<?php

namespace App\Validation;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class UniqueEntitiesInCollection extends Constraint
{
    public $fieldsOfChildren = null;
    public $collectionName = null;
    public $message = 'Tato kolekce musí obsahovat unikátní prvky.';

    public function getTargets()
    {
        return self::CLASS_CONSTRAINT;
    }
}