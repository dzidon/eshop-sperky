<?php

namespace App\Validation;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class PacketaId extends Constraint
{
    public $message = 'Tato pobočka Zásilkovny je neplatná, zvolte prosím jinou.';

    public function getTargets()
    {
        return self::CLASS_CONSTRAINT;
    }
}