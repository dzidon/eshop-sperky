<?php

namespace App\Form\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

/**
 * Subscriber řešící přidání checkboxu pro odstranění entity z kolekce
 *
 * @package App\Form\EventSubscriber
 */
class EntityMarkForRemovalSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            FormEvents::PRE_SET_DATA => 'preSetData',
        ];
    }

    public function preSetData(FormEvent $event): void
    {
        $instance = $event->getData();
        if ($instance && $instance->getId() !== null)
        {
            $event->getForm()->add('markedForRemoval', CheckboxType::class, [
                'label' => 'Smazat'
            ]);
        }
    }
}