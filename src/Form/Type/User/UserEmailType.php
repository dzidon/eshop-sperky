<?php

namespace App\Form\Type\User;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class UserEmailType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'constraints' => [
                new Email([
                    'message' => '{{ value }} není platná e-mailová adresa.',
                ]),
                new Length([
                    'max' => 180,
                    'maxMessage' => 'Maximální počet znaků v e-mailu: {{ limit }}',
                ]),
                new NotBlank([
                    'message' => 'Zadejte email.',
                ]),
            ],
        ]);
    }

    public function getParent(): string
    {
        return EmailType::class;
    }
}