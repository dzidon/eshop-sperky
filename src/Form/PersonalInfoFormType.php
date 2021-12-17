<?php

namespace App\Form;

use App\Entity\User;
use App\Form\Type\AgreePrivacyType;
use App\Form\Type\User as UserTypes;
use libphonenumber\PhoneNumberFormat;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Misd\PhoneNumberBundle\Form\Type\PhoneNumberType;

class PersonalInfoFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('gender', UserTypes\UserGenderType::class, [
                'label' => 'Oslovení',
            ])
            ->add('nameFirst', UserTypes\UserFirstNameType::class, [
                'required' => false,
                'label' => 'Jméno',
            ])
            ->add('nameLast', UserTypes\UserLastNameType::class, [
                'required' => false,
                'label' => 'Příjmení',
            ])
            ->add('phoneNumber', PhoneNumberType::class, [
                'required' => false,
                'default_region' => 'CZ',
                'format' => PhoneNumberFormat::INTERNATIONAL,
                'label' => 'Telefon',
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
            'data_class' => User::class,
            'csrf_protection' => true,
            'csrf_field_name' => '_token',
            'csrf_token_id'   => 'form_set_personal_info',
        ]);
    }
}