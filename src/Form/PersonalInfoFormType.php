<?php

namespace App\Form;

use App\Entity\User;
use App\Form\Type\AgreePrivacyType;
use App\Form\Type\User as UserTypes;
use libphonenumber\PhoneNumberFormat;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Misd\PhoneNumberBundle\Form\Type\PhoneNumberType;

class PersonalInfoFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('gender', UserTypes\UserGenderType::class)
            ->add('nameFirst', UserTypes\UserFirstNameType::class, [
                'required' => false,
            ])
            ->add('nameLast', UserTypes\UserLastNameType::class, [
                'required' => false,
            ])
            ->add('phoneNumber', PhoneNumberType::class, [
                'required' => false,
                'default_region' => 'CZ',
                'format' => PhoneNumberFormat::INTERNATIONAL])

            /*->add('rating', RatingType::class, [
                'mapped' => false,
            ])*/

            ->add('agreePrivacy', AgreePrivacyType::class)
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
