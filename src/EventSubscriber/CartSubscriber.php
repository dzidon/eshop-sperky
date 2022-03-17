<?php

namespace App\EventSubscriber;

use App\Service\CartService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Subscriber řešící nastavení tokenu aktivní objednávky.
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

    public function onKernelResponse(ResponseEvent $event)
    {
        $response = $event->getResponse();
        $tokenCookie = $this->cart->getCookieAndSaveOrder();

        if($tokenCookie !== null)
        {
            $response->headers->setCookie($tokenCookie);
        }
    }

    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::RESPONSE => 'onKernelResponse',
        ];
    }
}