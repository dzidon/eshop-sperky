<?php

namespace App\Form;

use App\Validation\Compound\EmailRequirements;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

class ResetPasswordRequestFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('email', EmailType::class, [
                'attr' => ['autocomplete' => 'email',
                           'autofocus' => 'autofocus'],
                'constraints' => [
                    new EmailRequirements(),
                    new NotBlank([
                        'message' => 'Zadejte email.',
                    ]),
                ],
                'data' => $options['email_empty_data'],
                'label' => 'Email',
                'help' => 'Zadejte e-mail, na který jste zaregistrovali svůj účet a my vám na něj pošleme odkaz pro resetování hesla.',
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