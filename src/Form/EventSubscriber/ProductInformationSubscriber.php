<?php

namespace App\Form\EventSubscriber;

use App\Entity\ProductInformation;
use App\Form\ProductInformationNewFormType;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\Extension\Core\Type\ButtonType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Security\Core\Security;

class ProductInformationSubscriber implements EventSubscriberInterface
{
    private Security $security;

    public function __construct(Security $security)
    {
        $this->security = $security;
    }

    public static function getSubscribedEvents(): array
    {
        return [FormEvents::PRE_SET_DATA => 'preSetData'];
    }

    public function preSetData(FormEvent $event): void
    {
        $form = $event->getForm();

        if($this->security->isGranted('product_info_edit'))
        {
            $form
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
                    'label' => 'Také můžete přidat novou skupinu informací',
                    'attr' => [
                        'class' => 'infoNew',
                        'data-reload-autocomplete' => true,
                    ],
                ])
                ->add('addItemNew', ButtonType::class, [
                    'attr' => [
                        'class' => 'btn-medium grey left js-add-item-link',
                        'data-collection-holder-class' => 'infoNew',
                    ],
                    'label' => 'Přidat informaci',
                ])
            ;
        }
    }
}