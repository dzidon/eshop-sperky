<?php

namespace App\Form\Type\User;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Choice;

class UserGenderType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'constraints' => [
                new Choice([
                    'choices' => [User::GENDER_ID_UNDISCLOSED, User::GENDER_ID_MALE, User::GENDER_ID_FEMALE],
                    'message' => 'Zvolte platné oslovení.',
                ]),
            ],
            'choices' => array(
                User::GENDER_NAME_UNDISCLOSED => User::GENDER_ID_UNDISCLOSED,
                User::GENDER_NAME_MALE => User::GENDER_ID_MALE,
                User::GENDER_NAME_FEMALE => User::GENDER_ID_FEMALE,
            ),
            'expanded' => true,
        ]);
    }

    public function getParent(): string
    {
        return ChoiceType::class;
    }
}