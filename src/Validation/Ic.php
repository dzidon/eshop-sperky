<?php

namespace App\Validation;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class Ic extends Constraint
{
    public $message = 'IČ musí být ve tvaru osmi číslic bez mezery.';
}