<?php

namespace App\Validation;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class CartOccurenceQuantity extends Constraint
{
    public $message = 'Bohužel máme na skladě jen {{ inventory }} ks produktu "{{ productName }}".';
}