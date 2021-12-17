<?php

namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class PasswordRepeatedType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'type' => PasswordType::class,
            'first_options' => self::getDefaultOptions('first'),
            'second_options' => self::getDefaultOptions('second'),
            'invalid_message' => 'Obě zadaná hesla se musí shodovat.',
            'mapped' => false,
        ]);
    }

    public function getParent(): string
    {
        return RepeatedType::class;
    }

    /**
     * Vrací defaultní data
     *
     * @param string $field
     * @return array
     */
    public static function getDefaultOptions(string $field): array
    {
        if($field === 'first')
        {
            return [
                'attr' => ['autocomplete' => 'new-password'],
                'label' => 'Nové heslo',
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
            ];
        }
        else if($field === 'second')
        {
            return [
                'attr' => ['autocomplete' => 'new-password'],
                'label' => 'Nové heslo znovu',
            ];
        }
        return [];
    }
}