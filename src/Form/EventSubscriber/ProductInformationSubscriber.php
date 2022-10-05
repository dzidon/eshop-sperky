<?php

namespace App\Form\EventSubscriber;

use App\Entity\Product;
use App\Entity\ProductInformation;
use App\Entity\ProductInformationGroup;
use App\Form\FormType\Admin\ProductInformationNewFormType;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\Extension\Core\Type\ButtonType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Validator\Constraints\Valid;

/**
 * Subscriber řešící vytváření nových skupin informací přímo ve formuláři pro editaci produktu.
 * To je možné pouze s odpovídajícím administrátorským oprávněním.
 *
 * @package App\Form\EventSubscriber
 */
class ProductInformationSubscriber implements EventSubscriberInterface
{
    private bool $canEditInfo;
    private array $allInfoGroups;

    public function __construct(Security $security)
    {
        $this->canEditInfo = $security->isGranted('product_info_edit');
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
        if($this->canEditInfo)
        {
            $infoGroupNames = [];

            /** @var ProductInformationGroup $infoGroup */
            foreach ($this->allInfoGroups as $infoGroup)
            {
                $infoGroupNames[] = $infoGroup->getName();
            }

            $event->getForm()
                ->add('infoNew', CollectionType::class, [
                    'mapped' => false,
                    'entry_type' => ProductInformationNewFormType::class,
                    'by_reference' => false,
                    'required' => false,
                    'allow_add' => true,
                    'allow_delete' => true,
                    'delete_empty' => function (ProductInformation $info = null) {
                        return $info === null || $info->getValue() === null || $info->getProductInformationGroup() === null;
                    },
                    'entry_options' => [
                        'autocomplete_items' => $infoGroupNames,
                        'constraints' => [
                            new Valid(),
                        ],
                    ],
                    'attr' => [
                        'class' => 'infoNew',
                        'data-reload-autocomplete' => true,
                    ],
                    'label' => false,
                ])
                ->add('addItemInfoNew', ButtonType::class, [
                    'attr' => [
                        'class' => 'btn-medium grey left js-add-item-link',
                        'data-collection-holder-class' => 'infoNew',
                    ],
                    'label' => 'Přidat informaci',
                ])
            ;
        }
    }

    public function postSubmit(FormEvent $event): void
    {
        if ($this->canEditInfo)
        {
            $form = $event->getForm();
            if ($form->isSubmitted() && $form->isValid())
            {
                /** @var Product $product */
                $product = $event->getData();
                if ($product)
                {
                    $allInfoNew = $form->get('infoNew')->getData();
                    if ($allInfoNew !== null)
                    {
                        /** @var ProductInformation $infoNew */
                        foreach ($allInfoNew as $infoNew)
                        {
                            $targetInfoGroup = null;

                            $inputInfoGroupName = $infoNew->getProductInformationGroup()->getName();

                            // Skupina už možná je přiřazená k produktu
                            foreach ($product->getInfo() as $info)
                            {
                                $infoGroup = $info->getProductInformationGroup();
                                if ($infoGroup->getName() === $inputInfoGroupName)
                                {
                                    $targetInfoGroup = $infoGroup;
                                    break;
                                }
                            }

                            // Skupina možná existuje v DB, ale není přiřazená k produktu
                            if ($targetInfoGroup === null)
                            {
                                /** @var ProductInformationGroup $infoGroup */
                                foreach ($this->allInfoGroups as $infoGroup)
                                {
                                    if ($infoGroup->getName() === $inputInfoGroupName)
                                    {
                                        $targetInfoGroup = $infoGroup;
                                        break;
                                    }
                                }
                            }

                            // Skupina neexistuje v DB a není přiřazena k produktu
                            if ($targetInfoGroup === null)
                            {
                                $targetInfoGroup = $infoNew->getProductInformationGroup();
                            }

                            $infoNew->setProductInformationGroup($targetInfoGroup);
                            $product->addInfo($infoNew);
                        }

                        $event->setData($product);
                    }
                }
            }
        }
    }

    public function setAllInfoGroups(array $allInfoGroups): self
    {
        $this->allInfoGroups = $allInfoGroups;

        return $this;
    }

    public function getAllInfoGroups(): array
    {
        return $this->allInfoGroups;
    }
}