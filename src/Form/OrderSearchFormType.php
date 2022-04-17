<?php

namespace App\Form;

use App\Entity\Order;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class OrderSearchFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('searchPhrase', TextType::class, [
                'required' => false,
                'label' => 'Hledat...',
            ])
            ->add('sortBy', ChoiceType::class, [
                'choices' => $options['sort_choices'],
                'invalid_message' => 'Zvolte platný atribut řazení.',
                'label' => 'Řazení',
            ])
            ->add('lifecycle', ChoiceType::class, [
                'required' => false,
                'choices' => array_flip(Order::LIFECYCLE_CHAPTERS),
                'invalid_message' => 'Zvolte platný stav objednávky.',
                'placeholder' => '-- všechny --',
                'label' => 'Stav objednávky',
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