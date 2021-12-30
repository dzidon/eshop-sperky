<?php

namespace App\Form;

use App\Entity\User;
use App\Form\Type\AgreePrivacyType;
use libphonenumber\PhoneNumberFormat;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Misd\PhoneNumberBundle\Form\Type\PhoneNumberType;

class PersonalInfoFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('gender', ChoiceType::class, [
                'choices' => array(
                    User::GENDER_NAME_UNDISCLOSED => User::GENDER_ID_UNDISCLOSED,
                    User::GENDER_NAME_MALE => User::GENDER_ID_MALE,
                    User::GENDER_NAME_FEMALE => User::GENDER_ID_FEMALE,
                ),
                'expanded' => true,
                'label' => 'Oslovení',
            ])
            ->add('nameFirst', TextType::class, [
                'required' => false,
                'label' => 'Jméno',
            ])
            ->add('nameLast', TextType::class, [
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