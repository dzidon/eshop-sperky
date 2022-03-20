<?php

namespace App\Form\EventSubscriber;

use App\Entity\Detached\CartInsert;
use App\Entity\ProductOption;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

/**
 *
 *
 * @package App\Form\EventSubscriber
 */
class CartOptionsSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            FormEvents::PRE_SET_DATA => 'preSetData',
        ];
    }

    public function preSetData(FormEvent $event): void
    {
        /** @var CartInsert $cartInsertData */
        $form = $event->getForm();
        $cartInsertData = $event->getData();
        $productOptions = $cartInsertData->getProduct()->getOptions();

        if (!$productOptions->isEmpty())
        {
            foreach ($productOptions as $option)
            {
                if ($option->isConfigured())
                {
                    if ($option->getType() === ProductOption::TYPE_DROPDOWN)
                    {
                        $items = $option->getParameters()->toArray();
                        $emptyData = (isset($items[0]) ? (string) $items[0]->getId() : '');

                        $form->add(sprintf('option%s', $option->getId()), ChoiceType::class, [
                            'mapped' => false,
                            'choices' => $items,
                            'choice_value' => 'id',
                            'choice_label' => 'value',
                            'empty_data' => $emptyData,
                            'label' => $option->getName(),
                            'preferred_choices' => ['muppets', 'arr'],
                            'error_bubbling' => true,
                        ]);
                    }
                    else if ($option->getType() === ProductOption::TYPE_NUMBER)
                    {

                    }
                }
            }
        }
    }
}