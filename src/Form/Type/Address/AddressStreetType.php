<?php

namespace App\Form\Type\Address;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Regex;

class AddressStreetType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'constraints' => [
                new Length([
                    'max' => 255,
                    'maxMessage' => 'Maximální počet znaků ulice a čísla popisného: {{ limit }}',
                ]),
                new Regex([
                    'pattern' => "/^(.*[^0-9]+) (([1-9][0-9]*)\/)?([1-9][0-9]*[a-cA-C]?)$/",
                    'message' => 'Neplatný tvar ulice a čísla popisného.',
                ]),
                new NotBlank([
                    'message' => 'Zadejte ulici a číslo popisné.',
                ]),
            ],
        ]);
    }

    public function getParent(): string
    {
        return TextType::class;
    }
}