<?php

namespace App\Service;

use App\Entity\Order;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Routing\RouterInterface;

/**
 * Třída řešící dokončení objednávky
 *
 * @package App\Service
 */
class OrderCompletionService
{
    private Order $order;

    private LoggerInterface $logger;
    private RouterInterface $router;
    private OrderEmailService $orderEmailService;

    public function __construct(LoggerInterface $logger, RouterInterface $router, OrderEmailService $orderEmailService)
    {
        $this->logger = $logger;
        $this->router = $router;
        $this->orderEmailService = $orderEmailService;
    }

    /**
     * Načte objednávku
     *
     * @param Order $order
     * @return $this
     */
    public function setOrder(Order $order): self
    {
        $this->order = $order;

        return $this;
    }

    /**
     * Nastaví objednávku do dokončeného stavu
     *
     * @return $this
     */
    public function finishOrder(): self
    {
        $this->order->finish();

        return $this;
    }

    /**
     * Pošle potvrzovací e-mail o dokončení objednávky
     *
     * @return $this
     */
    public function sendConfirmationEmail(): self
    {
        try
        {
            $this->orderEmailService
                ->initialize($this->order)
                ->send();
        }
        catch (TransportExceptionInterface $exception)
        {
            $this->logger->error(sprintf('Failed to send a confirmation e-mail for order ID %d, the following error occurred in method send: %s', $this->order->getId(), $exception->getMessage()));
        }

        return $this;
    }

    /**
     * Vytvoří odpověď pro přesměrování po dokončení objednávky
     *
     * @return RedirectResponse
     */
    public function getRedirectResponse(): RedirectResponse
    {
        $url = $this->router->generate('home');

        $redirectResponse = new RedirectResponse($url);
        if (!$this->order->isCreatedManually())
        {
            $redirectResponse->headers->clearCookie(CartService::COOKIE_NAME);
        }

        return $redirectResponse;
    }
}