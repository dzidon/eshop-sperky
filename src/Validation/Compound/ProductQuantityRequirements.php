<?php

namespace App\Validation\Compound;

use Symfony\Component\Validator\Constraints\Compound;
use Symfony\Component\Validator\Constraints\GreaterThan;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Type;

/**
 * @Annotation
 */
class ProductQuantityRequirements extends Compound
{
    protected function getConstraints(array $options): array
    {
        return [
            new Type([
                'type' => 'integer',
                'message' => 'Do počtu kusů musíte zadat celé číslo.',
            ]),
            new GreaterThan([
                'value' => 0,
                'message' => 'Počet kusů musí být větší než 0.',
            ]),
            new NotBlank([
                'message' => 'Počet kusů nesmí být prázdný.',
            ]),
        ];
    }
}