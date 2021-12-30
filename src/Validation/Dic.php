<?php

namespace App\Validation;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class Dic extends Constraint
{
    public $message = 'Neplatný tvar DIČ.';
}