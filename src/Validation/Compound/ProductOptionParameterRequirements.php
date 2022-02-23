<?php

namespace App\Validation\Compound;

use Symfony\Component\Validator\Constraints\Compound;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * @Annotation
 */
class ProductOptionParameterRequirements extends Compound
{
    protected function getConstraints(array $options): array
    {
        return [
            new NotBlank(),
            new Length([
                'max' => 255,
                'maxMessage' => 'Maximální počet znaků: {{ limit }}',
            ]),
        ];
    }
}