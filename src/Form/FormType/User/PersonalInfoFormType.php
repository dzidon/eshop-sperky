<?php

namespace App\Form\FormType\User;

use App\Entity\User;
use App\Form\EventSubscriber\AgreePrivacySubscriber;
use libphonenumber\PhoneNumberFormat;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Misd\PhoneNumberBundle\Form\Type\PhoneNumberType;

class PersonalInfoFormType extends AbstractType
{
    private AgreePrivacySubscriber $agreePrivacySubscriber;

    public function __construct(AgreePrivacySubscriber $agreePrivacySubscriber)
    {
        $this->agreePrivacySubscriber = $agreePrivacySubscriber;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nameFirst', TextType::class, [
                'attr' => ['autofocus' => 'autofocus'],
                'required' => false,
                'label' => 'Jméno',
            ])
            ->add('nameLast', TextType::class, [
                'required' => false,
                'label' => 'Příjmení',
            ])
            ->add('phoneNumber', PhoneNumberType::class, [
                'required' => false,
                'default_region' => 'CZ',
                'format' => PhoneNumberFormat::INTERNATIONAL,
                'label' => 'Telefon',
            ])
            ->addEventSubscriber($this->agreePrivacySubscriber)
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
            'csrf_protection' => true,
            'csrf_field_name' => '_token',
            'csrf_token_id'   => 'form_set_personal_info',
        ]);
    }
}