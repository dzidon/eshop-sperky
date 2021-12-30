<?php

namespace App\Validation\Compound;

use Symfony\Component\Validator\Constraints\Compound;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\Regex;

/**
 * @Annotation
 */
class StreetRequirements extends Compound
{
    protected function getConstraints(array $options): array
    {
        return [
            new Length([
                'max' => 255,
                'maxMessage' => 'Maximální počet znaků ulice a čísla popisného: {{ limit }}',
            ]),
            new Regex([
                'pattern' => "/^(.*[^0-9]+) (([1-9][0-9]*)\/)?([1-9][0-9]*[a-cA-C]?)$/",
                'message' => 'Neplatný tvar ulice a čísla popisného.',
            ]),
        ];
    }
}