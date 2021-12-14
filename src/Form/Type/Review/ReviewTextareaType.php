<?php

namespace App\Form\Type\Review;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;

class ReviewTextareaType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'constraints' => [
                new Length([
                    'max' => 255,
                    'maxMessage' => 'Maximální počet znaků v recenzi: {{ limit }}',
                ]),
            ],
        ]);
    }

    public function getParent(): string
    {
        return TextareaType::class;
    }
}