<?php

namespace App\Form\FormType\User;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

class VerificationFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('email', HiddenType::class, [
                'constraints' => [
                    new NotBlank(),
                ],
                'error_bubbling' => true,
                'data' => $options['default_email'],
            ])
            ->add('password', PasswordType::class, [
                'attr' => ['autofocus' => 'autofocus'],
                'constraints' => [
                    new NotBlank(),
                ],
                'label' => 'Heslo',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'csrf_protection' => true,
            'csrf_field_name' => '_token',
            'csrf_token_id'   => 'form_verification',
            'default_email'   => '',
        ]);

        $resolver->setAllowedTypes('default_email', 'string');
    }
}