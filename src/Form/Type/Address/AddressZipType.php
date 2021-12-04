<?php

namespace App\Form\Type\Address;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Regex;

class AddressZipType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'constraints' => [
                new Regex([
                    'pattern' => "/^\d{5}$/",
                    'message' => 'PSČ musí být ve tvaru pěti číslic bez mezery.',
                ]),
                new NotBlank([
                    'message' => 'Zadejte PSČ.',
                ]),
            ],
        ]);
    }

    public function getParent(): string
    {
        return TextType::class;
    }
}