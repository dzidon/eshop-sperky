<?php

namespace App\EventSubscriber;

use App\Service\Cart;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Subscriber řešící:
 *  - načtení aktivní objednávky před každou controllerovou akcí a její případnou synchronizaci na některých cestách
 *  - nastavení tokenu nové aktivní objednávky do cookie po vrácení odpovědi
 *
 * @package App\EventSubscriber
 */
class CartSubscriber implements EventSubscriberInterface
{
    private Cart $cart;

    public function __construct(Cart $cart)
    {
        $this->cart = $cart;
    }

    public function onKernelController()
    {
        $this->cart->load();
    }

    public function onKernelResponse(ResponseEvent $event)
    {
        // pokud se jedná o novou objednávku, nebo došlo k prodloužení platnosti, nastaví se token objednávky do cookie
        $newTokenCookie = $this->cart->getNewOrderCookie();
        if ($newTokenCookie !== null)
        {
            $event->getResponse()->headers->setCookie($newTokenCookie);
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