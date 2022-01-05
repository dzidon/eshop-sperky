<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SearchTextAndSortFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('vyraz', TextType::class, [
                'required' => false,
                'label' => 'Hledat',
            ])
            ->add('razeni', ChoiceType::class, [
                'choices' => $options['sort_choices'],
                'invalid_message' => 'Zvolte platný atribut řazení.',
                'label' => 'Seřadit podle',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'csrf_protection' => false,
            'method' => 'GET',
            'allow_extra_fields' => true,
            'sort_choices' => [],
        ]);

        $resolver->setAllowedTypes('sort_choices', 'array');
    }
}