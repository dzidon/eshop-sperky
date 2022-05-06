<?php

namespace App\Service;

use App\Entity\Order;
use App\Entity\Payment;
use App\Entity\User;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\RequestStack;
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
    private RequestStack $requestStack;
    private OrderEmailService $orderEmailService;

    public function __construct(Security $security, LoggerInterface $logger, RouterInterface $router, RequestStack $requestStack, OrderEmailService $orderEmailService)
    {
        $this->logger = $logger;
        $this->router = $router;
        $this->security = $security;
        $this->requestStack = $requestStack;
        $this->orderEmailService = $orderEmailService;
    }

    /**
     * Dokončí objednávku.
     *
     * @param Order $order
     * @return $this
     */
    public function finishOrder(Order $order): self
    {
        /** @var User|null $user */
        $user = $this->security->getUser();
        $order->finish($user);

        return $this;
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
     * @param Payment|null $payment
     * @return RedirectResponse
     */
    public function getCompletionRedirectResponse(Order $order, ?Payment $payment): RedirectResponse
    {
        if ($payment !== null && $payment->getGateUrl() !== null)
        {
            $url = $payment->getGateUrl();
        }
        else
        {
            $url = $this->router->generate('home');
            $flashBag = $this->requestStack->getCurrentRequest()->getSession()->getFlashBag();
            $flashBag->add('success', sprintf('Objednávka dokončena! Na e-mail %s jsme Vám poslali potvrzení.', $order->getEmail()));
        }

        $redirectResponse = new RedirectResponse($url);
        if (!$order->isCreatedManually())
        {
            $redirectResponse->headers->clearCookie(CartService::COOKIE_NAME);
        }

        return $redirectResponse;
    }
}