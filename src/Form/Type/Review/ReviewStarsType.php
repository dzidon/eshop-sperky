<?php

namespace App\Form\Type\Review;

use App\Entity\Review;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Choice;
use Symfony\Component\Validator\Constraints\NotBlank;

class ReviewStarsType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'choices' => array(
                Review::STAR_VALUES,
            ),
            'constraints' => [
                new Choice([
                    'choices' => Review::STAR_VALUES,
                    'message' => 'Vyberte platné hodnocení.',
                ]),
                new NotBlank([
                    'message' => 'Vyberte hodnocení.',
                ]),
            ],
            'expanded' => true,
        ]);
    }

    public function getParent(): string
    {
        return ChoiceType::class;
    }
}