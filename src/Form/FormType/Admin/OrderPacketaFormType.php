<?php

namespace App\Form\FormType\Admin;

use App\Entity\Order;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class OrderPacketaFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('weight', NumberType::class, [
                'invalid_message' => 'Musíte zadat číselnou hodnotu.',
                'label' => 'Hmotnost v kg',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class'        => Order::class,
            'csrf_protection'   => true,
            'csrf_field_name'   => '_token',
            'csrf_token_id'     => 'form_order_packeta',
            'validation_groups' => ['Default', 'admin_packeta'],
        ]);
    }
}