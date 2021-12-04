<?php

namespace App\Form\Type\Address;

use App\Entity\Address;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Choice;

class AddressCountryType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'choices'  => [
                Address::COUNTRY_NAMES[Address::COUNTRY_CODE_CZ] => Address::COUNTRY_CODE_CZ,
                Address::COUNTRY_NAMES[Address::COUNTRY_CODE_SK] => Address::COUNTRY_CODE_SK,
            ],
            'constraints' => [
                new Choice([
                    'choices' => [Address::COUNTRY_CODE_CZ, Address::COUNTRY_CODE_SK],
                    'message' => 'Zvolte platnou zemi.',
                ]),
            ],
        ]);
    }

    public function getParent(): string
    {
        return ChoiceType::class;
    }
}