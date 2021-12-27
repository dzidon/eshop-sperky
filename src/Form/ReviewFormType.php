<?php

namespace App\Form;

use App\Entity\Review;
use App\Form\Type\Review as ReviewTypes;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ReviewFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('stars', ReviewTypes\ReviewStarsType::class, [
                'label' => 'Hodnocení',
            ])
            ->add('text', ReviewTypes\ReviewTextareaType::class, [
                'required' => false,
                'label' => 'Text',
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'Uložit',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Review::class,
            'csrf_protection' => true,
            'csrf_field_name' => '_token',
            'csrf_token_id'   => 'form_review',
        ]);
    }
}