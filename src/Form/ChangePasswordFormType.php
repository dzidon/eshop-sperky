<?php

namespace App\Form;

use App\Form\Type\PasswordRepeatedType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ChangePasswordFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $plainPasswordFirstOptions = PasswordRepeatedType::getDefaultOptions('first');
        $plainPasswordFirstOptions['attr'] = ['autofocus' => 'autofocus'];

        $builder
            ->add('plainPassword', PasswordRepeatedType::class, [
                'first_options' => $plainPasswordFirstOptions,
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'Změnit',
            ])
        ;
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