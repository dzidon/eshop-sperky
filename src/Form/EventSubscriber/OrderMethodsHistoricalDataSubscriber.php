<?php

namespace App\Form\EventSubscriber;

use App\Entity\Order;
use LogicException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

/**
 * Subscriber řešící nastavování historických dat objednávky.
 *
 * @package App\Form\EventSubscriber
 */
class OrderMethodsHistoricalDataSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            FormEvents::POST_SUBMIT => 'submit',
        ];
    }

    public function submit(FormEvent $event): void
    {
        $order = $event->getData();
        if (!$order instanceof Order)
        {
            throw new LogicException(sprintf('%s musí dostat objekt třídy %s.', get_class($this), Order::class));
        }

        $order->saveHistoricalDataForMethods();
    }
}