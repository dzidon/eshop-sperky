<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class PasswordFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('repeated', RepeatedType::class, [
                'type' => PasswordType::class,
                'first_options' => [
                    'attr' => $options['first_options_attr'],
                    'constraints' => [
                        new NotBlank([
                            'message' => 'Zadejte heslo.',
                        ]),
                        new Length([
                            'min' => 6,
                            'minMessage' => 'Minimální počet znaků v hesle: {{ limit }}',
                            'max' => 4096,
                            'maxMessage' => 'Maximální počet znaků v hesle: {{ limit }}',
                        ]),
                    ],
                ],
                'second_options' => [
                    'attr' => ['autocomplete' => 'new-password'],
                ],
                'invalid_message' => 'Obě zadaná hesla se musí shodovat.',
                'mapped' => false,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'first_options_attr' => array(),
        ]);

        $resolver->setAllowedTypes('first_options_attr', 'array');
    }
}
