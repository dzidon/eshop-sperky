<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Email;
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
                    new NotBlank([
                        'message' => 'Zadejte email.',
                    ]),
                    new Email([
                        'message' => '{{ value }} není platná emailová adresa.',
                    ]),
                ],
                'data' => $options['email_empty_data'],
                'mapped' => false,
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
