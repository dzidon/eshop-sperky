<?php

namespace App\Form\FormType\Admin;

use App\Entity\ProductInformation;
use App\Form\DataTransformer\ProductInformationGroupToNameTransformer;
use App\Form\Type\AutoCompleteTextType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Valid;

class ProductInformationNewFormType extends AbstractType
{
    private ProductInformationGroupToNameTransformer $informationGroupToNameTransformer;

    public function __construct(ProductInformationGroupToNameTransformer $informationGroupToNameTransformer)
    {
        $this->informationGroupToNameTransformer = $informationGroupToNameTransformer;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('productInformationGroup', AutoCompleteTextType::class, [
                'data_autocomplete' => $options['autocomplete_items'],
                'constraints' => [
                    new Valid(),
                ],
                'label' => 'Název skupiny produktových informací',
            ])
            ->add('value', TextType::class, [
                'label' => 'Hodnota',
            ])
        ;

        $builder
            ->get('productInformationGroup')
            ->addModelTransformer($this->informationGroupToNameTransformer)
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class'         => ProductInformation::class,
            'csrf_protection'    => true,
            'csrf_field_name'    => '_token',
            'csrf_token_id'      => 'form_product_info_new',
            'autocomplete_items' => [],
        ]);

        $resolver->setAllowedTypes('autocomplete_items', 'array');
    }
}