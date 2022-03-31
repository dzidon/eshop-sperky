<?php

namespace App\Validation\Compound;

use App\Entity\Product;
use App\Validation\Vat;
use Symfony\Component\Validator\Constraints\Choice;
use Symfony\Component\Validator\Constraints\Compound;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Type;

/**
 * @Annotation
 */
class VatRequirements extends Compound
{
    protected function getConstraints(array $options): array
    {
        return [
            new Type([
                'type' => 'numeric',
                'message' => 'Musíte zadat číselnou hodnotu.',
            ]),
            new Choice([
                'choices' => Product::VAT_VALUES,
                'message' => 'Zvolte platnou hodnotu DPH.'
            ]),
            new Vat(),
            new NotBlank(),
        ];
    }
}