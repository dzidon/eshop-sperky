<?php

namespace App\Form;

use App\Entity\Address;
use App\Form\Type\Address as AddressTypes;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\IsTrue;

class AddressFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('alias', AddressTypes\AddressAliasType::class, [
                'attr' => ['autofocus' => 'autofocus'],
            ])
            ->add('country', AddressTypes\AddressCountryType::class)
            ->add('street', AddressTypes\AddressStreetType::class)
            ->add('additionalInfo', AddressTypes\AddressAdditionalInfoType::class, [
                'required' => false,
            ])
            ->add('town', AddressTypes\AddressTownType::class)
            ->add('zip', AddressTypes\AddressZipType::class)
            ->add('company', AddressTypes\AddressCompanyNameType::class, [
                'required' => false,
            ])
            ->add('ic', AddressTypes\AddressIcType::class, [
                'required' => false,
            ])
            ->add('dic', AddressTypes\AddressDicType::class, [
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
