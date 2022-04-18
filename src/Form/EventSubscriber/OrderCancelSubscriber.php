<?php

namespace App\Form\EventSubscriber;

use App\Entity\Order;
use App\Service\OrderCompletionService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

/**
 * Subscriber řešící zrušení objednávky v administraci
 *
 * @package App\Form\EventSubscriber
 */
class OrderCancelSubscriber implements EventSubscriberInterface
{
    private OrderCompletionService $orderCompletionService;

    public function __construct(OrderCompletionService $orderCompletionService)
    {
        $this->orderCompletionService = $orderCompletionService;
    }

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

            $this->orderCompletionService
                ->setOrder($order)
                ->cancelOrder($forceInventoryReplenish = false)
                ->sendConfirmationEmail()
            ;
        }
    }
}