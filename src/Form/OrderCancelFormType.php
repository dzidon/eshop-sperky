<?php

namespace App\Form;

use App\Entity\Order;
use App\Form\EventSubscriber\OrderCancelSubscriber;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class OrderCancelFormType extends AbstractType
{
    private OrderCancelSubscriber $orderCancelSubscriber;

    public function __construct(OrderCancelSubscriber $orderCancelSubscriber)
    {
        $this->orderCancelSubscriber = $orderCancelSubscriber;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('cartOccurencesWithProduct', CollectionType::class, [
                'entry_type' => CartOccurenceReplenishInventoryFormType::class,
                'label' => false,
            ])
            ->add('cancellationReason', TextareaType::class, [
                'attr' => [
                    'data-length' => 255,
                ],
                'label' => 'Důvod zrušení',
            ])
            ->addEventSubscriber($this->orderCancelSubscriber)
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class'        => Order::class,
            'csrf_protection'   => true,
            'csrf_field_name'   => '_token',
            'csrf_token_id'     => 'form_order_cancel',
            'validation_groups' => ['Default', 'cancellation'],
        ]);
    }
}