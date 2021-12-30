<?php

namespace App\Form;

use App\Entity\User;
use App\Form\Type\PasswordRepeatedType;
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
                'mapped' => false,
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
                'label' => 'Aktuální heslo',
            ])
            ->add('plainPassword', PasswordRepeatedType::class)
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
            'csrf_protection' => true,
            'csrf_field_name' => '_token',
            'csrf_token_id'   => 'form_password_change_logged_in',
            'validation_groups' => ['Default', 'validateNewPassword'],
        ]);
    }
}