<?php

namespace App\Form;

use App\Entity\Address;
use App\Entity\Order;
use App\Form\EventSubscriber\OrderAddressesSubscriber;
use libphonenumber\PhoneNumberFormat;
use Misd\PhoneNumberBundle\Form\Type\PhoneNumberType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class OrderAddressesFormType extends AbstractType
{
    private OrderAddressesSubscriber $addressesSubscriber;

    public function __construct(OrderAddressesSubscriber $addressesSubscriber)
    {
        $this->addressesSubscriber = $addressesSubscriber;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder

            // Doručovací adresa
            ->add('addressDeliveryNameFirst', TextType::class, [
                'attr' => ['autofocus' => 'autofocus'],
                'label' => 'Jméno',
            ])
            ->add('addressDeliveryNameLast', TextType::class, [
                'label' => 'Příjmení',
            ])
            ->add('email', EmailType::class, [
                'label' => 'Email',
            ])
            ->add('phoneNumber', PhoneNumberType::class, [
                'default_region' => 'CZ',
                'format' => PhoneNumberFormat::INTERNATIONAL,
                'label' => 'Telefon',
            ])

            // Firma
            ->add('companyChecked', CheckboxType::class, [
                'required' => false,
                'label' => 'Nakupuji na firmu',
                'attr' => [
                    'class' => 'company-checkbox',
                ],
            ])
            ->add('addressBillingCompany', TextType::class, [
                'required' => false,
                'label' => 'Název firmy',
            ])
            ->add('addressBillingIc', TextType::class, [
                'label' => 'IČ',
            ])
            ->add('addressBillingDic', TextType::class, [
                'label' => 'DIČ',
            ])

            // Fakturační adresa
            ->add('billingAddressChecked', CheckboxType::class, [
                'required' => false,
                'label' => 'Chci zadat jinou fakturační adresu',
                'attr' => [
                    'class' => 'billing-address-checkbox',
                ],
            ])
            ->add('addressBillingNameFirst', TextType::class, [
                'label' => 'Jméno',
            ])
            ->add('addressBillingNameLast', TextType::class, [
                'label' => 'Příjmení',
            ])
            ->add('addressBillingCountry', ChoiceType::class, [
                'choices' => Address::COUNTRY_NAMES_DROPDOWN,
                'label' => 'Země',
            ])
            ->add('addressBillingStreet', TextType::class, [
                'label' => 'Ulice a číslo popisné',
            ])
            ->add('addressBillingAdditionalInfo', TextType::class, [
                'required' => false,
                'label' => 'Doplněk adresy',
            ])
            ->add('addressBillingTown', TextType::class, [
                'label' => 'Obec',
            ])
            ->add('addressBillingZip', TextType::class, [
                'label' => 'PSČ',
            ])

            // Poznámka
            ->add('noteChecked', CheckboxType::class, [
                'required' => false,
                'label' => 'Chci zadat poznámku',
                'attr' => [
                    'class' => 'note-checkbox',
                ],
            ])
            ->add('note', TextareaType::class, [
                'attr' => [
                    'data-length' => 500,
                ],
                'label' => 'Poznámka',
            ])
            ->addEventSubscriber($this->addressesSubscriber)
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class'        => Order::class,
            'csrf_protection'   => true,
            'csrf_field_name'   => '_token',
            'csrf_token_id'     => 'form_order_addresses',
            'validation_groups' => function (FormInterface $form) {

                /** @var Order $order */
                $order = $form->getData();
                $groups = ['addresses'];

                if (!$order->isAddressDeliveryLocked())
                {
                    $groups[] = 'addresses_delivery';
                }

                if ($order->isCompanyChecked())
                {
                    $groups[] = 'addresses_company';
                }

                if ($order->isBillingAddressChecked())
                {
                    $groups[] = 'addresses_billing';
                }

                if ($order->isNoteChecked())
                {
                    $groups[] = 'addresses_note';
                }

                return $groups;
            },
        ]);
    }
}