<?php

namespace App\Form\EventSubscriber;

use App\Entity\Detached\Search\SearchProduct;
use App\Entity\ProductCategory;
use App\Repository\ProductCategoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

/**
 * Subscriber manipulující s produktovým filtrem v katalogu
 *
 * @package App\Form\EventSubscriber
 */
class ProductFilterSubscriber implements EventSubscriberInterface
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
            FormEvents::PRE_SUBMIT => 'preSubmit',
        ];
    }

    public function preSetData(FormEvent $event): void
    {
        /** @var SearchProduct $filterData */
        $filterData = $event->getData();
        $form = $event->getForm();

        if($filterData->getSection() !== null)
        {
            $form->add('categories', EntityType::class, [
                'class' => ProductCategory::class,
                'choice_label' => 'name',
                'invalid_message' => 'Snažíte se vyhledat neexistující kategorii.',
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

    public function preSubmit(FormEvent $event): void
    {
        /** @var SearchProduct $defaultData */
        $defaultData = $event->getForm()->getData();
        $submittedData = $event->getData();

        // min a max cena zadaná uživatelem
        if (!isset($submittedData['priceMin']) || !is_numeric($submittedData['priceMin']))
        {
            $submittedData['priceMin'] = $defaultData->getPriceMin();
        }

        if (!isset($submittedData['priceMax']) || !is_numeric($submittedData['priceMax']))
        {
            $submittedData['priceMax'] = $defaultData->getPriceMax();
        }

        // min a max hodnota musí být v daném intervalu
        if ($submittedData['priceMin'] < $defaultData->getPriceMin())
        {
            $submittedData['priceMin'] = $defaultData->getPriceMin();
        }

        if ($submittedData['priceMin'] > $defaultData->getPriceMax())
        {
            $submittedData['priceMin'] = $defaultData->getPriceMax();
        }

        if ($submittedData['priceMax'] < $defaultData->getPriceMin())
        {
            $submittedData['priceMax'] = $defaultData->getPriceMin();
        }

        if ($submittedData['priceMax'] > $defaultData->getPriceMax())
        {
            $submittedData['priceMax'] = $defaultData->getPriceMax();
        }

        // min hodnota nebude vetsi nez max hodnota
        if ($submittedData['priceMin'] > $submittedData['priceMax'])
        {
            $submittedData['priceMin'] = $submittedData['priceMax'];
        }

        $event->setData($submittedData);
    }
}