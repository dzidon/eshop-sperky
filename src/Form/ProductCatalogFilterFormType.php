<?php

namespace App\Form;

use App\Entity\Detached\ProductCatalogFilter;
use App\Entity\Product;
use App\Entity\ProductCategory;
use App\Form\EventSubscriber\ProductFilterSubscriber;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ProductCatalogFilterFormType extends AbstractType
{
    private EntityManagerInterface $entityManager;
    private ProductFilterSubscriber $categorySubscriber;

    public function __construct(EntityManagerInterface $entityManager, ProductFilterSubscriber $categorySubscriber)
    {
        $this->entityManager = $entityManager;
        $this->categorySubscriber = $categorySubscriber;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var ProductCatalogFilter $filterData */
        $filterData = $builder->getData();
        $priceData = $this->entityManager->getRepository(Product::class)->getMinAndMaxPrice( $filterData->getSection() );

        $filterData->setPriceMin($priceData['priceMin']);
        $filterData->setPriceMax($priceData['priceMax']);
        $filterData->setSortBy( Product::getSortDataForCatalog()[array_key_first(Product::getSortDataForCatalog())] );

        $builder->setData($filterData);
        $builder
            ->add('searchPhrase', TextType::class, [
                'required' => false,
                'label' => 'Hledat název nebo ID produktu',
            ])
            ->add('sortBy', ChoiceType::class, [
                'choices' => Product::getSortDataForCatalog(),
                'label' => 'Řazení',
            ])
            ->add('priceMin', NumberType::class, [
                'attr' => [
                    'data-price-min' => $priceData['priceMin'],
                ],
                'required' => false,
                'invalid_message' => 'Musíte zadat číselnou hodnotu.',
                'label' => 'Od',
            ])
            ->add('priceMax', NumberType::class, [
                'attr' => [
                    'data-price-max' => $priceData['priceMax'],
                ],
                'required' => false,
                'invalid_message' => 'Musíte zadat číselnou hodnotu.',
                'label' => 'Do',
            ])
            ->addEventSubscriber($this->categorySubscriber)
        ;
    }

    /**
     * Po sestavení view chceme přidat počty produktů k jednotlivým kategoriím
     *
     * @param FormView $view
     * @param FormInterface $form
     * @param array $options
     */
    public function finishView(FormView $view, FormInterface $form, array $options)
    {
        /** @var ProductCatalogFilter $filterData */
        $filterData = $form->getData();

        if ($filterData->getSection() !== null)
        {
            $section = $filterData->getSection();
            $searchPhrase = $filterData->getSearchPhrase();
            $priceMin = $filterData->getPriceMin();
            $priceMax = $filterData->getPriceMax();

            $categoriesChosen = $filterData->getCategoriesGrouped();
            $categoriesToRender = $view->children['categories'];
            $categoriesGrouped = $view->children['categories']->vars['choices'];

            foreach ($categoriesGrouped as $groupName => $choiceGroupView)
            {
                foreach ($choiceGroupView->choices as $choiceView)
                {
                    /** @var ProductCategory $category */
                    $category = $choiceView->data;
                    $repository = $this->entityManager->getRepository(ProductCategory::class);

                    if(!$filterData->getCategories()->contains($category))
                    {
                        if($form->isSubmitted() && $form->isValid())
                        {
                            $count = $repository->getNumberOfProductsForFilter($category, $section, $searchPhrase, $priceMin, $priceMax, $categoriesChosen);
                        }
                        else
                        {
                            $count = $repository->getNumberOfProductsForFilter($category, $section);
                        }

                        if($count > 0)
                        {
                            $plusSign = '';
                            if($categoriesChosen && array_key_exists($groupName, $categoriesChosen))
                            {
                                $plusSign = '+';
                            }
                            $categoriesToRender->children[$category->getId()]->vars['label'] = sprintf('%s (%s%s)', $category->getName(), $plusSign, $count);
                        }
                        else
                        {
                            unset($categoriesToRender->vars['choices'][$groupName]->choices[$category->getId()]);
                            unset($categoriesToRender->children[$category->getId()]);

                            if(count($categoriesToRender->vars['choices'][$groupName]->choices) === 0)
                            {
                                unset($categoriesToRender->vars['choices'][$groupName]);
                            }
                        }
                    }
                }
            }
        }
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
            'validation_groups' => false, // validace se řeší v ProductFilterSubscriber, aby neházela errory
            'attr' => [
                'id' => 'form-product-catalog',
            ],
        ]);
    }
}