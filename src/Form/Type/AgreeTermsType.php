<?php

namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\IsTrue;

class AgreeTermsType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'mapped' => false,
            'constraints' => [
                new IsTrue([
                    'message' => 'Musíte souhlasit s podmínkami používání.',
                ]),
            ],
        ]);
    }

    public function getParent(): string
    {
        return CheckboxType::class;
    }
}