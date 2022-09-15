<?php

namespace App\Form\FormType\User;

use App\Entity\DeliveryMethod;
use App\Entity\Order;
use App\Entity\PaymentMethod;
use App\Form\EventSubscriber\OrderMethodsSubscriber;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class OrderMethodsFormType extends AbstractType
{
    private OrderMethodsSubscriber $orderMethodsSubscriber;

    public function __construct(OrderMethodsSubscriber $orderMethodsSubscriber)
    {
        $this->orderMethodsSubscriber = $orderMethodsSubscriber;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('deliveryMethod', EntityType::class, [
                'class' => DeliveryMethod::class,
                'multiple' => false,
                'expanded' => true,
                'choice_label' => 'name',
                'label' => 'Způsob dopravy',
            ])
            ->add('paymentMethod', EntityType::class, [
                'class' => PaymentMethod::class,
                'multiple' => false,
                'expanded' => true,
                'choice_label' => 'name',
                'label' => 'Způsob platby',
            ])
            ->add('staticAddressDeliveryAdditionalInfo', HiddenType::class, [
                'attr' => [
                    'class' => 'staticAddressDeliveryAdditionalInfo',
                ],
            ])
            ->add('staticAddressDeliveryCountry', HiddenType::class, [
                'attr' => [
                    'class' => 'staticAddressDeliveryCountry',
                ],
            ])
            ->add('staticAddressDeliveryStreet', HiddenType::class, [
                'attr' => [
                    'class' => 'staticAddressDeliveryStreet',
                ],
            ])
            ->add('staticAddressDeliveryTown', HiddenType::class, [
                'attr' => [
                    'class' => 'staticAddressDeliveryTown',
                ],
            ])
            ->add('staticAddressDeliveryZip', HiddenType::class, [
                'attr' => [
                    'class' => 'staticAddressDeliveryZip',
                ],
            ])
            ->addEventSubscriber($this->orderMethodsSubscriber)
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class'        => Order::class,
            'csrf_protection'   => true,
            'csrf_field_name'   => '_token',
            'csrf_token_id'     => 'form_order_methods',
            'validation_groups' => ['Default', 'methods'],
            'attr'              => [
                'id' => 'form-order-methods'
            ],
        ]);
    }
}