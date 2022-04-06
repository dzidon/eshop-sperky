<?php

namespace App\Form;

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
            ->addEventSubscriber($this->orderMethodsSubscriber)
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class'        => Order::class,
            'csrf_protection'   => true,
            'csrf_field_name'   => '_token',
            'csrf_token_id'     => 'form_cart_methods',
            'attr'              => [
                'id' => 'form-order-methods'
            ],
        ]);
    }
}