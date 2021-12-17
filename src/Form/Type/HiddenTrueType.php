<?php

namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

class HiddenTrueType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'mapped' => false,
            'data' => '1',
            'constraints' => [
                new NotBlank([
                    'message' => 'Něco se nezdařilo, zkuste to znovu.',
                ]),
            ],
        ]);
    }

    public function getParent(): string
    {
        return HiddenType::class;
    }
}