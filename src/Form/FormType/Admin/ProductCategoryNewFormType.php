<?php

namespace App\Form\FormType\Admin;

use App\Form\DataTransformer\ProductCategoryGroupToNameTransformer;
use App\Form\DataTransformer\ProductCategoryToNameTransformer;
use App\Form\Type\AutoCompleteTextType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Valid;

class ProductCategoryNewFormType extends AbstractType
{
    private ProductCategoryGroupToNameTransformer $categoryGroupToNameTransformer;
    private ProductCategoryToNameTransformer $categoryToNameTransformer;

    public function __construct(ProductCategoryGroupToNameTransformer $categoryGroupToNameTransformer, ProductCategoryToNameTransformer $categoryToNameTransformer)
    {
        $this->categoryGroupToNameTransformer = $categoryGroupToNameTransformer;
        $this->categoryToNameTransformer = $categoryToNameTransformer;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('categoryGroup', AutoCompleteTextType::class, [
                'data_autocomplete' => $options['autocomplete_items'],
                'constraints' => [
                    new Valid(),
                ],
                'label' => 'Název skupiny kategorií',
            ])
            ->add('category', TextType::class, [
                'constraints' => [
                    new Valid(),
                ],
                'label' => 'Název kategorie',
            ])
        ;

        $builder
            ->get('categoryGroup')
            ->addModelTransformer($this->categoryGroupToNameTransformer)
        ;

        $builder
            ->get('category')
            ->addModelTransformer($this->categoryToNameTransformer)
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'csrf_protection'    => true,
            'csrf_field_name'    => '_token',
            'csrf_token_id'      => 'form_product_category_new',
            'autocomplete_items' => [],
        ]);

        $resolver->setAllowedTypes('autocomplete_items', 'array');
    }
}