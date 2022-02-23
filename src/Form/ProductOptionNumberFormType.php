<?php

namespace App\Form;

use App\Entity\Detached\ProductOptionNumberParameters;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ProductOptionNumberFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('min', TextType::class, [
                'data' => $options['empty_parameter_values']['min'],
                'label' => 'Minimální povolené číslo',
            ])
            ->add('max', TextType::class, [
                'data' => $options['empty_parameter_values']['max'],
                'label' => 'Maximální povolené číslo',
            ])
            ->add('default', TextType::class, [
                'data' => $options['empty_parameter_values']['default'],
                'label' => 'Výchozí číslo',
            ])
            ->add('step', TextType::class, [
                'data' => $options['empty_parameter_values']['step'],
                'help' => 'O kolik se má změnit číslo, když uživatel klikne na šipku pro zvětšení/zmenšení?',
                'label' => 'Číselná změna',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ProductOptionNumberParameters::class,
            'csrf_protection' => true,
            'csrf_field_name' => '_token',
            'csrf_token_id'   => 'form_product_option_number',
            'empty_parameter_values' => [
                'min' => '',
                'max' => '',
                'default' => '',
                'step' => '',
            ],
        ]);

        $resolver->setAllowedTypes('empty_parameter_values', 'array');
    }
}