<?php

namespace App\Form;

use App\Entity\User;
use libphonenumber\PhoneNumberFormat;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\IsTrue;
use Misd\PhoneNumberBundle\Form\Type\PhoneNumberType;

class PersonalInfoFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('gender', ChoiceType::class, [
                'choices' => array(
                    User::GENDER_NAME_MALE => User::GENDER_ID_MALE,
                    User::GENDER_NAME_FEMALE => User::GENDER_ID_FEMALE,
                ),
                'expanded' => true,
            ])
            ->add('nameFirst', TextType::class, [
                'required' => false,
            ])
            ->add('nameLast', TextType::class, [
                'required' => false,
            ])
            ->add('phoneNumber', PhoneNumberType::class, [
                'required' => false,
                'default_region' => 'CZ',
                'format' => PhoneNumberFormat::INTERNATIONAL])
            ->add('agreePrivacy', CheckboxType::class, [
                'mapped' => false,
                'constraints' => [
                    new IsTrue([
                        'message' => 'Musíte souhlasit se zpracováním osobních údajů.',
                    ]),
                ],
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
