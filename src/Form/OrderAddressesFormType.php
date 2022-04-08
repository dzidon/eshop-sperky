<?php

namespace App\Form;

use App\Entity\Order;
use App\Form\EventSubscriber\OrderAddressesSubscriber;
use libphonenumber\PhoneNumberFormat;
use Misd\PhoneNumberBundle\Form\Type\PhoneNumberType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
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

                return $groups;
            },
        ]);
    }
}