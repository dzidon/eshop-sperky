<?php

namespace App\EventSubscriber;

use App\Service\CartService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Subscriber řešící nastavení tokenu aktivní objednávky do cookie po vrácení odpovědi a vložení varování
 * vzniklých při synchronizaci do FlashBagu.
 *
 * @package App\EventSubscriber
 */
class CartSubscriber implements EventSubscriberInterface
{
    private CartService $cart;

    public function __construct(CartService $cart)
    {
        $this->cart = $cart;
    }

    public function onKernelController(ControllerEvent $event)
    {
        // varování vzniklá při synchronizaci
        if($this->cart->hasSynchronizationWarnings())
        {
            foreach ($this->cart->getAndRemoveSynchronizationWarnings() as $warning)
            {
                $event->getRequest()->getSession()->getFlashBag()->add('warning', $warning);
            }
        }
    }

    public function onKernelResponse(ResponseEvent $event)
    {
        // cookie
        $tokenCookie = $this->cart->getOrderCookie();
        if($tokenCookie !== null)
        {
            $event->getResponse()->headers->setCookie($tokenCookie);
        }
    }

    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::CONTROLLER => 'onKernelController',
            KernelEvents::RESPONSE => 'onKernelResponse',
        ];
    }
}