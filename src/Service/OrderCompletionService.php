<?php

namespace App\Service;

use App\Entity\Order;
use App\Entity\User;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Security;

/**
 * Třída manipulující s dokončenou objednávkou
 *
 * @package App\Service
 */
class OrderCompletionService
{
    private Order $order;

    private Security $security;
    private LoggerInterface $logger;
    private RouterInterface $router;
    private OrderEmailService $orderEmailService;

    public function __construct(Security $security, LoggerInterface $logger, RouterInterface $router, OrderEmailService $orderEmailService)
    {
        $this->security = $security;
        $this->logger = $logger;
        $this->router = $router;
        $this->orderEmailService = $orderEmailService;
    }

    /**
     * Nastaví objednávku do dokončeného stavu
     *
     * @param Order $order
     * @return $this
     */
    public function finishOrder(Order $order): self
    {
        $this->order = $order;

        /** @var User|null $user */
        $user = $this->security->getUser();
        $this->order->finish($user);

        return $this;
    }

    /**
     * Nastaví objednávku do zrušeného stavu
     *
     * @param Order $order
     * @param bool $forceInventoryReplenish
     * @return $this
     */
    public function cancelOrder(Order $order, bool $forceInventoryReplenish): self
    {
        $this->order = $order;
        $this->order->cancel($forceInventoryReplenish);

        return $this;
    }

    /**
     * Pošle potvrzovací e-mail o změně stavu objednávky
     *
     * @return $this
     */
    public function sendConfirmationEmail(): self
    {
        try
        {
            $this->orderEmailService->send($this->order);
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