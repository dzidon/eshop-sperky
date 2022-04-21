<?php

namespace App\Validation;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class PacketaExists extends Constraint
{
    public $message = 'Nejdříve pošlete zásilku do systému Zásilkovny.';

    public function getTargets()
    {
        return self::CLASS_CONSTRAINT;
    }
}