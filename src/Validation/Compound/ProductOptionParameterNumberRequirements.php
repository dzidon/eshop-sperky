<?php

namespace App\Validation\Compound;

use Symfony\Component\Validator\Constraints\Compound;
use Symfony\Component\Validator\Constraints\Type;

/**
 * @Annotation
 */
class ProductOptionParameterNumberRequirements extends Compound
{
    protected function getConstraints(array $options): array
    {
        return [
            new ProductOptionParameterRequirements(),
            new Type([
                'type' => 'numeric',
                'message' => 'Musíte zadat číselnou hodnotu.'
            ]),
        ];
    }
}