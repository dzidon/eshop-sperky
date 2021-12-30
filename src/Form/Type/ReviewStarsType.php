<?php

namespace App\Form\Type;

use App\Entity\Review;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ReviewStarsType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'block_prefix' => 'star_choice',
            'choices' => Review::STAR_VALUES,
            'expanded' => true,
        ]);
    }

    public function getParent(): string
    {
        return ChoiceType::class;
    }
}