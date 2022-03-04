<?php

namespace App\Form;

use App\Entity\Detached\ProductCatalogFilter;
use App\Entity\Product;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ProductCatalogFilterFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('searchPhrase', TextType::class, [
                'required' => false,
                'label' => 'Hledat',
            ])
            ->add('sortBy', ChoiceType::class, [
                'choices' => Product::getSortDataForCatalog(),
                'label' => 'Řazení',
            ])
            ->add('priceMin', NumberType::class, [
                'invalid_message' => 'Musíte zadat číselnou hodnotu.',
                'label' => 'Od',
            ])
            ->add('priceMax', NumberType::class, [
                'invalid_message' => 'Musíte zadat číselnou hodnotu.',
                'label' => 'Do',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ProductCatalogFilter::class,
            'csrf_protection' => true,
            'csrf_field_name' => '_token',
            'csrf_token_id'   => 'form_product_catalog_filter',
        ]);
    }
}