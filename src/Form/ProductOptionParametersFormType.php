<?php

namespace App\Form;

use App\Entity\ProductOption;
use App\Form\EventSubscriber\ProductOptionParametersSubscriber;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ProductOptionParametersFormType extends AbstractType
{
    private ProductOptionParametersSubscriber $optionParametersSubscriber;

    public function __construct(ProductOptionParametersSubscriber $optionParametersSubscriber)
    {
        $this->optionParametersSubscriber = $optionParametersSubscriber;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->addEventSubscriber($this->optionParametersSubscriber);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ProductOption::class,
            'csrf_protection' => true,
            'csrf_field_name' => '_token',
            'csrf_token_id'   => 'form_product_option',
        ]);
    }
}