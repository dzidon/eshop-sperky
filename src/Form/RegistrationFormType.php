<?php

namespace App\Form;

use App\Entity\User;
use App\Form\Type\AgreeTermsType;
use EWZ\Bundle\RecaptchaBundle\Form\Type\EWZRecaptchaType;
use EWZ\Bundle\RecaptchaBundle\Validator\Constraints\IsTrue as RecaptchaTrue;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class RegistrationFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('email', EmailType::class, [
                'attr' => ['autofocus' => 'autofocus'],
            ])
            ->add('plainPassword', PasswordFormType::class, [
                'mapped' => false,
                'first_options_attr' => ['autocomplete' => 'new-password'],
            ])
            ->add('agreeTerms', AgreeTermsType::class)
            ->add('recaptcha', EWZRecaptchaType::class, [
                'mapped' => false,
                'constraints' => array(
                    new RecaptchaTrue()
                )
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
