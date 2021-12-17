<?php

namespace App\Form;

use App\Entity\User;
use App\Form\Type\AgreeTermsType;
use App\Form\Type\PasswordRepeatedType;
use App\Form\Type\User as UserTypes;
use EWZ\Bundle\RecaptchaBundle\Form\Type\EWZRecaptchaType;
use EWZ\Bundle\RecaptchaBundle\Validator\Constraints\IsTrue as RecaptchaTrue;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class RegistrationFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $plainPasswordFirstOptions = PasswordRepeatedType::getDefaultOptions('first');
        $plainPasswordFirstOptions['label'] = 'Heslo';

        $plainPasswordSecondOptions = PasswordRepeatedType::getDefaultOptions('second');
        $plainPasswordSecondOptions['label'] = 'Heslo znovu';

        $builder
            ->add('email', UserTypes\UserEmailType::class, [
                'attr' => ['autofocus' => 'autofocus'],
                'label' => 'Email',
            ])
            ->add('plainPassword', PasswordRepeatedType::class, [
                'mapped' => false,
                'first_options' => $plainPasswordFirstOptions,
                'second_options' => $plainPasswordSecondOptions,
            ])
            ->add('agreeTerms', AgreeTermsType::class, [
                'label' => 'Souhlasím s podmínkami používání',
            ])
            ->add('recaptcha', EWZRecaptchaType::class, [
                'mapped' => false,
                'constraints' => [
                    new RecaptchaTrue()
                ],
                'label' => false,
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'Zaregistrovat se',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
            'csrf_protection' => true,
            'csrf_field_name' => '_token',
            'csrf_token_id'   => 'form_registration',
        ]);
    }
}