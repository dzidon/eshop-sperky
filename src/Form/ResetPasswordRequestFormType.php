<?php

namespace App\Form;

use App\Form\Type\User\UserEmailType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ResetPasswordRequestFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('email', UserEmailType::class, [
                'attr' => ['autocomplete' => 'email',
                           'autofocus' => 'autofocus'],
                'data' => $options['email_empty_data'],
                'label' => 'Email',
                'help' => 'Zadejte e-mail, na který jste zaregistrovali svůj účet a my vám na něj pošleme odkaz pro resetování hesla.',
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'Poslat odkaz',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'csrf_protection' => true,
            'csrf_field_name' => '_token',
            'csrf_token_id'   => 'form_password_reset_email',
            'email_empty_data' => '',
        ]);

        $resolver->setAllowedTypes('email_empty_data', 'string');
    }
}