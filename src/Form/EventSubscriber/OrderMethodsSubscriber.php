<?php

namespace App\Form\EventSubscriber;

use App\Entity\Order;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

/**
 * Subscriber řešící nastavování adresy podle Zásilkovny
 *
 * @package App\Form\EventSubscriber
 */
class OrderMethodsSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            FormEvents::PRE_SET_DATA => 'preSetData',
            FormEvents::POST_SUBMIT => 'submit',
        ];
    }

    public function preSetData(FormEvent $event): void
    {
        /** @var Order $order */
        $order = $event->getData();
        if ($order->deliveryMethodLocksDeliveryAddress())
        {
            $order->injectStaticAddressDelivery();
        }
    }

    public function submit(FormEvent $event): void
    {
        /** @var Order $order */
        $order = $event->getData();
        $order->determineAddressDelivery();
    }
}