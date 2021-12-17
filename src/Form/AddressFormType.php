<?php

namespace App\Form;

use App\Entity\Address;
use App\Form\Type\Address as AddressTypes;
use App\Form\Type\AgreePrivacyType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AddressFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('alias', AddressTypes\AddressAliasType::class, [
                'attr' => ['autofocus' => 'autofocus'],
                'label' => 'Alias',
            ])
            ->add('country', AddressTypes\AddressCountryType::class, [
                'label' => 'Země',
            ])
            ->add('street', AddressTypes\AddressStreetType::class, [
                'label' => 'Ulice a číslo popisné',
            ])
            ->add('additionalInfo', AddressTypes\AddressAdditionalInfoType::class, [
                'required' => false,
                'label' => 'Doplněk adresy',
            ])
            ->add('town', AddressTypes\AddressTownType::class, [
                'label' => 'Obec',
            ])
            ->add('zip', AddressTypes\AddressZipType::class, [
                'label' => 'PSČ',
            ])
            ->add('company', AddressTypes\AddressCompanyNameType::class, [
                'required' => false,
                'label' => 'Název firmy',
            ])
            ->add('ic', AddressTypes\AddressIcType::class, [
                'required' => false,
                'label' => 'IČ',
            ])
            ->add('dic', AddressTypes\AddressDicType::class, [
                'required' => false,
                'label' => 'DIČ',
            ])
            ->add('agreePrivacy', AgreePrivacyType::class, [
                'label' => 'Souhlasím se zpracováním osobních údajů',
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'Uložit',
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