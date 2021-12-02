<?php

namespace App\Validation;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class AllOrNone extends Constraint
{
    public $message = 'Vyplnili jste některý z firemních údajů, musíte vyplnit i tento.';
    public $targetAttributes = [];

    public function getTargets()
    {
        return self::CLASS_CONSTRAINT;
    }
}