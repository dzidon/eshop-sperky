<?php

namespace App\Validation\Compound;

use Symfony\Component\Validator\Constraints\Compound;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\Length;

/**
 * @Annotation
 */
class EmailRequirements extends Compound
{
    protected function getConstraints(array $options): array
    {
        return [
            new Email([
                'message' => '{{ value }} není platná e-mailová adresa.',
            ]),
            new Length([
                'max' => 180,
                'maxMessage' => 'Maximální počet znaků v e-mailu: {{ limit }}',
            ]),
        ];
    }
}