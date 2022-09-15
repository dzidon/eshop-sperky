<?php

namespace App\Form\FormType\Search\Composition;

use App\Entity\Detached\Search\Composition\ProductFilter;
use App\Entity\ProductCategory;
use App\Form\EventSubscriber\ProductFilterSubscriber;
use App\Form\EventSubscriber\SearchSubscriber;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ProductCatalogFilterFormType extends AbstractType
{
    private SearchSubscriber $searchSubscriber;
    private EntityManagerInterface $entityManager;
    private ProductFilterSubscriber $filterSubscriber;

    public function __construct(SearchSubscriber $searchSubscriber, EntityManagerInterface $entityManager, ProductFilterSubscriber $filterSubscriber)
    {
        $this->searchSubscriber = $searchSubscriber;
        $this->entityManager = $entityManager;
        $this->filterSubscriber = $filterSubscriber;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var ProductFilter $filterData */
        $filterData = $builder->getData();

        $builder
            ->add('phraseSort', PhraseSortFormType::class)
            ->add('priceMin', NumberType::class, [
                'attr' => [
                    'data-price-min' => $filterData->getPriceMin(),
                ],
                'required' => false,
                'invalid_message' => 'Musíte zadat číselnou hodnotu.',
                'label' => 'Od',
            ])
            ->add('priceMax', NumberType::class, [
                'attr' => [
                    'data-price-max' => $filterData->getPriceMax(),
                ],
                'required' => false,
                'invalid_message' => 'Musíte zadat číselnou hodnotu.',
                'label' => 'Do',
            ])
            ->addEventSubscriber($this->filterSubscriber)
            ->addEventSubscriber($this->searchSubscriber)
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
        /** @var ProductFilter $filterData */
        $filterData = $form->getData();

        if ($filterData->getSection() !== null)
        {
            $searchPhrase = $filterData->getPhraseSort()->getPhrase()->getText();
            $section = $filterData->getSection();
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
            'data_class' => ProductFilter::class,
            'csrf_protection' => false,
            'csrf_field_name' => '_token',
            'csrf_token_id'   => 'form_product_catalog_filter',
            'method' => 'GET',
            'allow_extra_fields' => true,
            'attr' => [
                'id' => 'form-product-catalog',
            ],
        ]);
    }
}