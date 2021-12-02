<?php

namespace App\Form;

use App\Entity\Address;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\IsTrue;

class AddressFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('alias', TextType::class, [
                'attr' => ['autofocus' => 'autofocus'],
            ])
            ->add('country', ChoiceType::class, [
                'choices'  => [
                    Address::COUNTRY_NAMES[Address::COUNTRY_CODE_CZ] => Address::COUNTRY_CODE_CZ,
                    Address::COUNTRY_NAMES[Address::COUNTRY_CODE_SK] => Address::COUNTRY_CODE_SK,
                ],
            ])
            ->add('street', TextType::class)
            ->add('town', TextType::class)
            ->add('zip', TextType::class)
            ->add('company', TextType::class, [
                'required' => false,
            ])
            ->add('ic', TextType::class, [
                'required' => false,
            ])
            ->add('dic', TextType::class, [
                'required' => false,
            ])
            ->add('agreePrivacy', CheckboxType::class, [
                'mapped' => false,
                'constraints' => [
                    new IsTrue([
                        'message' => 'Musíte souhlasit se zpracováním osobních údajů.',
                    ]),
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Address::class,
            'csrf_protection' => true,
            'csrf_field_name' => '_token',
            'csrf_token_id'   => 'form_address',
        ]);
    }
}
