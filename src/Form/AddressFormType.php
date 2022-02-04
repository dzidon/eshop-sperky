<?php

namespace App\Form;

use App\Entity\Address;
use App\Form\Type\AgreePrivacyType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AddressFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('alias', TextType::class, [
                'attr' => ['autofocus' => 'autofocus'],
                'label' => 'Alias',
            ])
            ->add('country', ChoiceType::class, [
                'choices' => Address::COUNTRY_NAMES_DROPDOWN,
                'label' => 'Země',
            ])
            ->add('street', TextType::class, [
                'label' => 'Ulice a číslo popisné',
            ])
            ->add('additionalInfo', TextType::class, [
                'required' => false,
                'label' => 'Doplněk adresy',
            ])
            ->add('town', TextType::class, [
                'label' => 'Obec',
            ])
            ->add('zip', TextType::class, [
                'label' => 'PSČ',
            ])
            ->add('company', TextType::class, [
                'required' => false,
                'label' => 'Název firmy',
            ])
            ->add('ic', TextType::class, [
                'required' => false,
                'label' => 'IČ',
            ])
            ->add('dic', TextType::class, [
                'required' => false,
                'label' => 'DIČ',
            ])
            ->add('agreePrivacy', AgreePrivacyType::class, [
                'label' => 'Souhlasím se zpracováním osobních údajů',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Address::class,
            'csrf_protection' => true,
            'csrf_field_name' => '_token',
            'csrf_token_id'   => 'form_address',
        ]);
    }
}