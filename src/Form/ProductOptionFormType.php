<?php

namespace App\Form;

use App\Entity\ProductOption;
use App\Form\EventSubscriber\ProductOptionSubscriber;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ProductOptionFormType extends AbstractType
{
    private ProductOptionSubscriber $productOptionSubscriber;

    public function __construct(ProductOptionSubscriber $productOptionSubscriber)
    {
        $this->productOptionSubscriber = $productOptionSubscriber;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'attr' => ['autofocus' => 'autofocus'],
                'label' => 'Název',
            ])
            ->add('type', ChoiceType::class, [
                'choices' => ProductOption::TYPE_NAMES,
                'label' => 'Typ',
            ])
            ->addEventSubscriber($this->productOptionSubscriber)
        ;
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