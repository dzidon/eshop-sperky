<?php

namespace App\Form\EventSubscriber;

use App\Entity\Product;
use App\Entity\ProductCategory;
use App\Entity\ProductCategoryGroup;
use App\Form\FormType\Admin\ProductCategoryNewFormType;
use Symfony\Component\Console\Exception\LogicException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\Extension\Core\Type\ButtonType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Validator\Constraints\Valid;

/**
 * Subscriber řešící vytváření nových skupin kategorií a nových kategorií přímo ve formuláři pro editaci produktu.
 * To je možné pouze s odpovídajícím administrátorským oprávněním.
 *
 * @package App\Form\EventSubscriber
 */
class ProductCategorySubscriber implements EventSubscriberInterface
{
    private bool $canEditCategories;
    private array $allCategoryGroups;

    public function __construct(Security $security)
    {
        $this->canEditCategories = $security->isGranted('product_category_edit');
    }

    public static function getSubscribedEvents(): array
    {
        return [
            FormEvents::PRE_SET_DATA => 'preSetData',
            FormEvents::POST_SUBMIT => 'postSubmit',
        ];
    }

    public function preSetData(FormEvent $event): void
    {
        if ($this->canEditCategories)
        {
            $categoryGroupNames = [];

            /** @var ProductCategoryGroup $categoryGroup */
            foreach ($this->allCategoryGroups as $categoryGroup)
            {
                $categoryGroupNames[] = $categoryGroup->getName();
            }

            $event
                ->getForm()
                ->add('categoriesNew', CollectionType::class, [
                    'mapped' => false,
                    'entry_type' => ProductCategoryNewFormType::class,
                    'by_reference' => false,
                    'required' => false,
                    'allow_add' => true,
                    'allow_delete' => true,
                    'delete_empty' => function (array $data = null) {
                        return $data === null || $data['categoryGroup'] === null || $data['category'] === null;
                    },
                    'entry_options' => [
                        'autocomplete_items' => $categoryGroupNames,
                        'constraints' => [
                            new Valid(),
                        ],
                    ],
                    'attr' => [
                        'class' => 'categoryNew',
                        'data-reload-autocomplete' => true,
                    ],
                    'label' => false,
                ])
                ->add('addItemCategoryNew', ButtonType::class, [
                    'attr' => [
                        'class' => 'btn-medium grey left js-add-item-link',
                        'data-collection-holder-class' => 'categoryNew',
                    ],
                    'label' => 'Přidat kategorii',
                ])
            ;
        }
    }

    public function postSubmit(FormEvent $event): void
    {
        if ($this->canEditCategories)
        {
            $form = $event->getForm();
            if ($form->isSubmitted() && $form->isValid())
            {
                $product = $event->getData();
                if (!$product instanceof Product)
                {
                    throw new LogicException(sprintf('%s musí dostat objekt třídy %s.', get_class($this), Product::class));
                }

                foreach ($form->get('categoriesNew')->getData() as $input)
                {
                    /** @var ProductCategoryGroup $inputCategoryGroup */
                    $inputCategoryGroup = $input['categoryGroup'];

                    /** @var ProductCategory $inputCategory */
                    $inputCategory = $input['category'];

                    $targetCategoryGroup = null;

                    // Prohledáme kategorie produktu. Možná už k produktu existuje kategorie se skupinou, která má zadávaný název (a ještě možná není v DB).
                    foreach ($product->getCategories() as $category)
                    {
                        $categoryGroup = $category->getProductCategoryGroup();
                        if($categoryGroup->getName() === $inputCategoryGroup->getName())
                        {
                            $targetCategoryGroup = $categoryGroup;
                            break;
                        }
                    }

                    // Nenašlo to nic v kolekci produktu, ještě ale může existovat v DB.
                    if($targetCategoryGroup === null)
                    {
                        foreach ($this->allCategoryGroups as $categoryGroup)
                        {
                            if ($categoryGroup->getName() === $inputCategoryGroup->getName())
                            {
                                $targetCategoryGroup = $categoryGroup;
                                break;
                            }
                        }
                    }

                    // Našlo to nějakou skupinu buď u produktu nebo v db
                    if ($targetCategoryGroup !== null)
                    {
                        $targetCategory = $inputCategory;

                        // Kategorie se zadávaným jménem už mozná existuje v nalezené skupině.
                        foreach ($targetCategoryGroup->getCategories() as $category)
                        {
                            if($category->getName() === $inputCategory->getName())
                            {
                                $targetCategory = $category;
                                break;
                            }
                        }

                        // Pokud kategorie se zadávaným jménem ještě neexistuje v nalezené skupině, přidáme ji tam.
                        // Pokud už kategorie v nalezené skupině existuje, pouze se připojí k produktu.
                        if($targetCategory === $inputCategory)
                        {
                            $targetCategoryGroup->addCategory($targetCategory);
                        }

                        $product->addCategory($targetCategory);
                    }
                    else // Žádná skupina se zadávaným jménem neexistuje, vytváříme novou.
                    {
                        $targetCategoryGroup = $inputCategoryGroup;
                        $targetCategoryGroup->addCategory($inputCategory);
                        $product->addCategory($inputCategory);
                    }
                }

                $event->setData($product);
            }
        }
    }

    public function getAllCategoryGroups(): array
    {
        return $this->allCategoryGroups;
    }

    public function setAllCategoryGroups(array $allCategoryGroups): self
    {
        $this->allCategoryGroups = $allCategoryGroups;

        return $this;
    }
}