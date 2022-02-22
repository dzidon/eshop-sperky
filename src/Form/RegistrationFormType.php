<?php

namespace App\Form;

use App\Entity\User;
use App\Form\EventSubscriber\PasswordHashSubscriber;
use App\Form\Type\AgreePrivacyType;
use App\Form\Type\AgreeTermsType;
use App\Form\Type\PasswordRepeatedType;
use EWZ\Bundle\RecaptchaBundle\Form\Type\EWZRecaptchaType;
use EWZ\Bundle\RecaptchaBundle\Validator\Constraints\IsTrue as RecaptchaTrue;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class RegistrationFormType extends AbstractType
{
    private PasswordHashSubscriber $passwordHashSubscriber;

    public function __construct(PasswordHashSubscriber $passwordHashSubscriber)
    {
        $this->passwordHashSubscriber = $passwordHashSubscriber;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('email', EmailType::class, [
                'attr' => ['autofocus' => 'autofocus'],
                'label' => 'Email',
            ])
            ->add('plainPassword', PasswordRepeatedType::class, [
                'first_options' => [
                    'label' => 'Heslo'
                ],
                'second_options' => [
                    'label' => 'Heslo znovu'
                ],
            ])
            ->add('agreePrivacy', AgreePrivacyType::class)
            ->add('agreeTerms', AgreeTermsType::class)
            ->add('recaptcha', EWZRecaptchaType::class, [
                'mapped' => false,
                'constraints' => [
                    new RecaptchaTrue()
                ],
                'label' => false,
            ])
            ->addEventSubscriber($this->passwordHashSubscriber)
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
            'csrf_protection' => true,
            'csrf_field_name' => '_token',
            'csrf_token_id'   => 'form_registration',
            'validation_groups' => ['Default', 'validateNewPassword', 'validateEmail'],
        ]);
    }
}