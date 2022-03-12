<?php

namespace App\Form\EventSubscriber;

use App\Entity\Detached\ProductCatalogFilter;
use App\Entity\ProductCategory;
use App\Repository\ProductCategoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Subscriber manipulující s produktovým filtrem v katalogu
 *
 * @package App\Form\EventSubscriber
 */
class ProductFilterSubscriber implements EventSubscriberInterface
{
    private ProductCatalogFilter $defaultModel;

    private EntityManagerInterface $entityManager;
    private ValidatorInterface $validator;

    public function __construct(EntityManagerInterface $entityManager, ValidatorInterface $validator)
    {
        $this->entityManager = $entityManager;
        $this->validator = $validator;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            FormEvents::PRE_SET_DATA => 'preSetData',
            FormEvents::POST_SET_DATA => 'postSetData',
            FormEvents::PRE_SUBMIT => 'preSubmit',
        ];
    }

    public function preSetData(FormEvent $event): void
    {
        /** @var ProductCatalogFilter $filterData */
        $filterData = $event->getData();
        $form = $event->getForm();

        if($filterData->getSection() !== null)
        {
            $form->add('categories', EntityType::class, [
                'class' => ProductCategory::class,
                'choice_label' => 'name',
                'query_builder' => function (ProductCategoryRepository $er) use ($filterData) {
                    return $er->qbFindCategoriesInSection($filterData->getSection());
                },
                'group_by' => function(ProductCategory $category) {
                    return $category->getProductCategoryGroup()->getName();
                },
                'multiple' => true,
                'expanded' => true,
                'required' => false,
                'label' => false,
            ]);
        }
    }

    public function postSetData(FormEvent $event): void
    {
        $this->defaultModel = clone $event->getData(); // defaultní data
    }

    public function preSubmit(FormEvent $event): void
    {
        $data = $event->getData(); // data zadaná uživatelem

        if(count($this->validator->validatePropertyValue(ProductCatalogFilter::class, 'sortBy', $data['sortBy'])))
        {
            $data['sortBy'] = $this->defaultModel->getSortBy();
        }

        if(count($this->validator->validatePropertyValue(ProductCatalogFilter::class, 'priceMin', $data['priceMin']))
           || $data['priceMin'] > $this->defaultModel->getPriceMax() || $data['priceMin'] < $this->defaultModel->getPriceMin())
        {
            $data['priceMin'] = $this->defaultModel->getPriceMin();
        }

        if(count($this->validator->validatePropertyValue(ProductCatalogFilter::class, 'priceMax', $data['priceMax']))
           || $data['priceMax'] > $this->defaultModel->getPriceMax() || $data['priceMax'] < $this->defaultModel->getPriceMin())
        {
            $data['priceMax'] = $this->defaultModel->getPriceMax();
        }

        if ($data['priceMin'] > $data['priceMax'])
        {
            $data['priceMin'] = $this->defaultModel->getPriceMin();
            $data['priceMax'] = $this->defaultModel->getPriceMax();
        }

        $event->setData($data);
    }
}