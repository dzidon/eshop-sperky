<?php

namespace App\Form\EventSubscriber;

use App\Entity\ProductOption;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

/**
 * Subscriber řešící změnu typu produktové volby. V případě změny produktové volby by mělo dojít k vymazání parametrů.
 *
 * @package App\Form\EventSubscriber
 */
class ProductOptionSubscriber implements EventSubscriberInterface
{
    private $oldOption = null;

    public static function getSubscribedEvents(): array
    {
        return [
            FormEvents::PRE_SET_DATA => 'preSetData',
            FormEvents::POST_SUBMIT => 'postSubmit',
        ];
    }

    public function preSetData(FormEvent $event): void
    {
        /** @var ProductOption $option Produktová volba před naplněním novými daty. */
        $option = $event->getData();
        if ($option && $option->getId() !== null)
        {
            $this->oldOption = clone $option;
        }
    }

    public function postSubmit(FormEvent $event): void
    {
        $form = $event->getForm();
        if ($form->isSubmitted() && $form->isValid())
        {
            /** @var ProductOption $option Produktová volba po správném vyplnění a odeslání formuláře */
            $option = $event->getData();
            if ($option && $this->oldOption)
            {
                if ($option->getType() !== $this->oldOption->getType())
                {
                    $option->getParameters()->clear();
                }
            }
        }
    }
}