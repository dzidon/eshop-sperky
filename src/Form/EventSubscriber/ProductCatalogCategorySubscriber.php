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

/**
 * Subscriber řešící přidání kategorií do filtru
 *
 * @package App\Form\EventSubscriber
 */
class ProductCatalogCategorySubscriber implements EventSubscriberInterface
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            FormEvents::PRE_SET_DATA => 'preSetData',
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
}