<?php

namespace App\Form\Type\Address;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;

class AddressAdditionalInfoType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'constraints' => [
                new Length([
                    'max' => 255,
                    'maxMessage' => 'Maximální počet znaků v doplňku adresy: {{ limit }}',
                ]),
            ],
        ]);
    }

    public function getParent(): string
    {
        return TextType::class;
    }
}