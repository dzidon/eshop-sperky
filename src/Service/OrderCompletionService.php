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
        /** @var User|null $user */
        $user = $this->security->getUser();
        $this->order->finish($user);

        return $this;
    }

    /**
     * Nastaví objednávku do zrušeného stavu
     *
     * @param bool $forceInventoryReplenish
     * @return $this
     */
    public function cancelOrder(bool $forceInventoryReplenish): self
    {
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