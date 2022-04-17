<?php

namespace App\Form\EventSubscriber;

use App\Entity\Order;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

/**
 * Subscriber řešící výpočet ceny s DPH všech CartOccurences po vytvoření objednávky na míru
 *
 * @package App\Form\EventSubscriber
 */
class CustomOrderEditSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            FormEvents::POST_SUBMIT => 'postSubmit',
        ];
    }

    public function postSubmit(FormEvent $event): void
    {
        $form = $event->getForm();
        if ($form->isSubmitted() && $form->isValid())
        {
            /** @var Order $order */
            $order = $event->getData();

            foreach ($order->getCartOccurences() as $cartOccurence)
            {
                $cartOccurence->calculatePriceWithVat();
            }
        }
    }
}