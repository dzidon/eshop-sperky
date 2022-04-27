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
 * Třída manipulující s objednávkou od dokončení.
 *
 * @package App\Service
 */
class OrderPostCompletionService
{
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
     * Dokončí objednávku a vrátí odpověď pro přesměrování.
     *
     * @param Order $order
     * @return RedirectResponse
     */
    public function finishOrderAndGetResponse(Order $order): RedirectResponse
    {
        /** @var User|null $user */
        $user = $this->security->getUser();
        $order->finish($user);

        return $this->getCompletionRedirectResponse($order);
    }

    /**
     * Pošle potvrzovací e-mail o změně stavu objednávky.
     *
     * @param Order $order
     * @return $this
     */
    public function sendConfirmationEmail(Order $order): self
    {
        try
        {
            $this->orderEmailService->send($order);
        }
        catch (TransportExceptionInterface $exception)
        {
            $this->logger->error(sprintf('Failed to send a confirmation e-mail for order ID %d, the following error occurred in method send: %s', $order->getId(), $exception->getMessage()));
        }

        return $this;
    }

    /**
     * Vytvoří odpověď pro přesměrování po dokončení objednávky.
     *
     * @param Order $order
     * @return RedirectResponse
     */
    private function getCompletionRedirectResponse(Order $order): RedirectResponse
    {
        $url = $this->router->generate('home');

        $redirectResponse = new RedirectResponse($url);
        if (!$order->isCreatedManually())
        {
            $redirectResponse->headers->clearCookie(CartService::COOKIE_NAME);
        }

        return $redirectResponse;
    }
}