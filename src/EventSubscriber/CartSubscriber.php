<?php

namespace App\EventSubscriber;

use App\Service\CartService;
use App\Service\OrderSynchronizer\OrderCartSynchronizer;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Subscriber řešící:
 *  - načtení aktivní objednávky před každou controllerovou akcí a její případnou synchronizaci na některých cestách
 *  - nastavení tokenu aktivní objednávky do cookie po vrácení odpovědi
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
        // načtení aktivní objednávky a případná synchronizace
        $currentRoute = $event->getRequest()->attributes->get('_route');
        $loadFully = isset(OrderCartSynchronizer::SYNCHRONIZATION_ROUTES[$currentRoute]);
        $this->cart->initialize($loadFully);
    }

    public function onKernelResponse(ResponseEvent $event)
    {
        // cookie
        $tokenCookie = $this->cart->getOrderCookie();
        if ($tokenCookie !== null)
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