<?php

namespace App\Validation;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class ZipCode extends Constraint
{
    public $message = 'PSČ musí být ve tvaru pěti číslic bez mezery.';
}