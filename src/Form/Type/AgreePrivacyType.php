<?php

namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\IsTrue;

class AgreePrivacyType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'mapped' => false,
            'block_prefix' => 'privacy_checkbox',
            'constraints' => [
                new IsTrue([
                    'message' => 'Musíte souhlasit se zpracováním osobních údajů.',
                ]),
            ],
        ]);
    }

    public function getParent(): string
    {
        return CheckboxType::class;
    }
}