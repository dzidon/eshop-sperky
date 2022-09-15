<?php

namespace App\Form\EventSubscriber;

use App\Entity\Product;
use App\Entity\ProductInformation;
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
    private Security $security;

    private bool $canEditInfo;

    public function __construct(Security $security)
    {
        $this->security = $security;

        $this->canEditInfo = $this->security->isGranted('product_info_edit');
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
                    $infoNew = $form->get('infoNew')->getData();
                    if ($infoNew !== null)
                    {
                        foreach ($infoNew as $objectToBeAdded)
                        {
                            if($objectToBeAdded !== null)
                            {
                                $product->addInfo($objectToBeAdded);
                            }
                        }
                        $event->setData($product);
                    }
                }
            }
        }
    }
}