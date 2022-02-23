<?php

namespace App\Form\EventSubscriber;

use App\Entity\Detached\ProductOptionNumberParameters;
use App\Entity\ProductOption;
use App\Entity\ProductOptionParameter;
use App\Form\ProductOptionDropdownFormType;
use App\Form\ProductOptionNumberFormType;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\Extension\Core\Type\ButtonType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Validator\Constraints\Valid;

/**
 * Subscriber řešící formulář pro konfiguraci produktové volby
 *
 * @package App\Form\EventSubscriber
 */
class ProductOptionParametersSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            FormEvents::PRE_SET_DATA => 'preSetData',
            FormEvents::POST_SUBMIT => 'postSubmit',
        ];
    }

    public function preSetData(FormEvent $event): void
    {
        /** @var ProductOption $option */
        $option = $event->getData();
        $form = $event->getForm();

        if ($option->getType() === ProductOption::TYPE_DROPDOWN)
        {
            $form
                ->add('parameters', CollectionType::class, [
                    'entry_type' => ProductOptionDropdownFormType::class,
                    'by_reference' => false,
                    'required' => false,
                    'allow_add' => true,
                    'allow_delete' => true,
                    'label' => 'Obsah rozbalovacího seznamu',
                    'delete_empty' => function (ProductOptionParameter $parameter = null) {
                        return $parameter === null || $parameter->getValue() === null;
                    },
                    'attr' => [
                        'class' => 'parameters',
                    ],
                ])
                ->add('addItem', ButtonType::class, [
                    'attr' => [
                        'class' => 'btn-medium grey left js-add-item-link',
                        'data-collection-holder-class' => 'parameters',
                    ],
                    'label' => 'Přidat hodnotu',
                ])
            ;
        }
        else if ($option->getType() === ProductOption::TYPE_NUMBER)
        {
            $form
                ->add('parametersNumeric', ProductOptionNumberFormType::class, [
                    'empty_parameter_values' => [
                        'min' => $option->getParameterValue('min'),
                        'max' => $option->getParameterValue('max'),
                        'default' => $option->getParameterValue('default'),
                        'step' => $option->getParameterValue('step'),
                    ],
                    'constraints' => [
                        new Valid(),
                    ],
                    'mapped' => false,
                    'label' => false,
                ])
            ;
        }
    }

    public function postSubmit(FormEvent $event): void
    {
        $form = $event->getForm();
        if ($form->isSubmitted() && $form->isValid())
        {
            /** @var ProductOption $option */
            $option = $event->getData();
            if ($option)
            {
                $data = [];

                if ($option->getType() === ProductOption::TYPE_NUMBER)
                {
                    /** @var ProductOptionNumberParameters $parametersNumeric */
                    $parametersNumeric = $form->get('parametersNumeric')->getData();

                    $data = [
                        'min' => $parametersNumeric->getMin(),
                        'max' => $parametersNumeric->getMax(),
                        'default' => $parametersNumeric->getDefault(),
                        'step' => $parametersNumeric->getStep(),
                    ];
                }

                $option->configure($data);
                $event->setData($option);
            }
        }
    }
}