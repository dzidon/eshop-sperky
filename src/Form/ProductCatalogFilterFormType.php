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
                'property_path' => 'searchPhrase',
                'label' => 'Hledat název nebo ID produktu',
            ])
            ->add('sortBy', ChoiceType::class, [
                'choices' => Product::getSortDataForCatalog(),
                'empty_data' => Product::getSortDataForCatalog()[array_key_first(Product::getSortDataForCatalog())],
                'label' => 'Řazení',
            ])
            ->add('priceMin', NumberType::class, [
                'attr' => [
                    'data-price-min' => $options['price_min'],
                ],
                'required' => false,
                'invalid_message' => 'Musíte zadat číselnou hodnotu.',
                'label' => 'Od',
            ])
            ->add('priceMax', NumberType::class, [
                'attr' => [
                    'data-price-max' => $options['price_max'],
                ],
                'required' => false,
                'invalid_message' => 'Musíte zadat číselnou hodnotu.',
                'label' => 'Do',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ProductCatalogFilter::class,
            'csrf_protection' => false,
            'csrf_field_name' => '_token',
            'csrf_token_id'   => 'form_product_catalog_filter',
            'method' => 'GET',
            'allow_extra_fields' => true,
            'price_min' => 0,
            'price_max' => 0,
        ]);

        $resolver->setAllowedTypes('price_min', 'numeric');
        $resolver->setAllowedTypes('price_max', 'numeric');
    }
}