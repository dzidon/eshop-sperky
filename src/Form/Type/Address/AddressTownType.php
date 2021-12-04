<?php

namespace App\Form\Type\Address;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class AddressTownType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'constraints' => [
                new Length([
                    'max' => 255,
                    'maxMessage' => 'Maximální počet znaků v obci: {{ limit }}',
                ]),
                new NotBlank([
                    'message' => 'Zadejte obec.',
                ]),
            ],
        ]);
    }

    public function getParent(): string
    {
        return TextType::class;
    }
}