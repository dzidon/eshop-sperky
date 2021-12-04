<?php

namespace App\Form\Type\User;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;

class UserFirstNameType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'constraints' => [
                new Length([
                    'max' => 255,
                    'maxMessage' => 'Maximální počet znaků v křestním jméně: {{ limit }}',
                ]),
            ],
        ]);
    }

    public function getParent(): string
    {
        return TextType::class;
    }
}