<?php

namespace App\Form;

use App\Entity\Detached\ProductCatalogFilter;
use App\Entity\Product;
use App\Form\EventSubscriber\ProductCatalogCategorySubscriber;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ProductCatalogFilterFormType extends AbstractType
{
    private EntityManagerInterface $entityManager;
    private ProductCatalogCategorySubscriber $categorySubscriber;

    public function __construct(EntityManagerInterface $entityManager, ProductCatalogCategorySubscriber $categorySubscriber)
    {
        $this->entityManager = $entityManager;
        $this->categorySubscriber = $categorySubscriber;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var ProductCatalogFilter $filterData */
        $filterData = $builder->getData();
        $section = $filterData->getSection();
        $priceData = $this->entityManager->getRepository(Product::class)->getMinAndMaxPrice($section);

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
                    'data-price-min' => $priceData['priceMin'],
                ],
                'data' => $priceData['priceMin'],
                'required' => false,
                'invalid_message' => 'Musíte zadat číselnou hodnotu.',
                'label' => 'Od',
            ])
            ->add('priceMax', NumberType::class, [
                'attr' => [
                    'data-price-max' => $priceData['priceMax'],
                ],
                'data' => $priceData['priceMax'],
                'required' => false,
                'invalid_message' => 'Musíte zadat číselnou hodnotu.',
                'label' => 'Do',
            ])
            ->addEventSubscriber($this->categorySubscriber)
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
        ]);
    }
}