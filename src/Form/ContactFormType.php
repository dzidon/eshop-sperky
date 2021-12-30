<?php

namespace App\Form;

use App\Validation\Compound\EmailRequirements;
use EWZ\Bundle\RecaptchaBundle\Form\Type\EWZRecaptchaType;
use EWZ\Bundle\RecaptchaBundle\Validator\Constraints\IsTrue as RecaptchaTrue;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class ContactFormType extends AbstractType
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
                'label' => 'Váš email',
            ])
            ->add('subject', TextType::class, [
                'constraints' => [
                    new Length([
                        'max' => 64,
                        'maxMessage' => 'Maximální počet znaků v předmětu: {{ limit }}',
                    ]),
                    new NotBlank([
                        'message' => 'Zadejte předmět.',
                    ]),
                ],
                'label' => 'Předmět',
            ])
            ->add('text', TextareaType::class, [
                'constraints' => [
                    new Length([
                        'max' => 4096,
                        'maxMessage' => 'Maximální počet znaků v textu: {{ limit }}',
                    ]),
                    new NotBlank([
                        'message' => 'Zadejte text.',
                    ]),
                ],
                'label' => 'Text',
            ])
            ->add('recaptcha', EWZRecaptchaType::class, [
                'constraints' => [
                    new RecaptchaTrue()
                ],
                'label' => false,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'csrf_protection' => true,
            'csrf_field_name' => '_token',
            'csrf_token_id'   => 'form_contact',
            'email_empty_data' => '',
        ]);

        $resolver->setAllowedTypes('email_empty_data', 'string');
    }
}