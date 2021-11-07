<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Validator\Constraints\UserPassword;
use Symfony\Component\Validator\Constraints\NotBlank;

class ChangePasswordLoggedInFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('oldPlainPassword', PasswordType::class, [
                'attr' => ['autocomplete' => 'new-password',
                           'autofocus' => 'autofocus'],
                'constraints' => [
                    new NotBlank([
                        'message' => 'Zadejte heslo.',
                    ]),
                    new UserPassword([
                        'message' => 'Zadal jste špatné heslo.',
                    ]),
                ],
            ])
            ->add('newPlainPassword', PasswordFormType::class, [
                'first_options_attr' => ['autocomplete' => 'new-password'],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'csrf_protection' => true,
            'csrf_field_name' => '_token',
            'csrf_token_id'   => 'form_password_change',
        ]);
    }
}