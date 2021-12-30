<?php

namespace App\Form;

use App\Entity\User;
use App\Form\Type\PasswordRepeatedType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ChangePasswordFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('plainPassword', PasswordRepeatedType::class, [
                'first_options'  => [
                    'label' => 'Nové heslo',
                    'attr' => [
                        'autofocus' => 'autofocus',
                    ],
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
            'csrf_token_id'   => 'form_password_change',
            'validation_groups' => ['Default', 'validateNewPassword'],
        ]);
    }
}