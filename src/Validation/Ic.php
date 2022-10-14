<?php

namespace App\Validation;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class Ic extends Constraint
{
    public $message = 'IČ musí být zadáno ve správném tvaru.';
}