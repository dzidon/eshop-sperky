<?php

namespace App\Validation;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class Vat extends Constraint
{
    public $message = 'Nemůžete nastavit DPH větší než 0%, nejsme plátci DPH.';
}