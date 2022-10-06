<?php

namespace App\Form\EventSubscriber;

use App\Entity\Order;
use LogicException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

/**
 * Subscriber řešící nastavování adresy podle Zásilkovny.
 *
 * @package App\Form\EventSubscriber
 */
class OrderStaticDeliveryAddressSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            FormEvents::PRE_SET_DATA => 'preSetData',
        ];
    }

    public function preSetData(FormEvent $event): void
    {
        $order = $event->getData();
        if (!$order instanceof Order)
        {
            throw new LogicException(sprintf('%s musí dostat objekt třídy %s.', get_class($this), Order::class));
        }

        if ($order->deliveryMethodLocksDeliveryAddress())
        {
            $order->injectAddressDeliveryToStatic();
        }
    }
}