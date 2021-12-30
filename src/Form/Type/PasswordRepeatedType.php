<?php

namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PasswordRepeatedType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(
        [
            'type' => PasswordType::class,
            'invalid_message' => 'Obě zadaná hesla se musí shodovat.',
            'options' => [
                'attr' => [
                    'autocomplete' => 'new-password'
                ],
            ],
            'first_options'  => [
                'label' => 'Nové heslo'
            ],
            'second_options' => [
                'label' => 'Nové heslo znovu'
            ],
        ]);
    }

    public function getParent(): string
    {
        return RepeatedType::class;
    }
}