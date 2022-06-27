<?php

namespace App\Service;

use App\Entity\CartOccurence;
use App\Entity\Order;
use App\Entity\Payment;
use App\Entity\PaymentMethod;
use App\Entity\User;
use DateTime;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Uid\Uuid;

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
     * Nastaví objednávku do dokončeného stavu. Neřeší ukládání.
     *
     * @param Order $order
     * @return $this
     */
    public function finishOrder(Order $order): self
    {
        $order->setLifecycleChapter(Order::LIFECYCLE_AWAITING_PAYMENT);

        // nastavení částky dobírky + objednávka na dobírku bude rovnou připravená na odeslání
        if ($order->getPaymentMethod() !== null && $order->getPaymentMethod()->getType() === PaymentMethod::TYPE_ON_DELIVERY)
        {
            $cashOnDelivery = $order->getTotalPriceWithVat($withMethods = true);
            $order->setCashOnDelivery(ceil($cashOnDelivery));
            $order->setLifecycleChapter(Order::LIFECYCLE_AWAITING_SHIPPING);
        }

        // nezaškrtl, že chce zadat firmu
        if (!$order->isCompanyChecked())
        {
            $order->resetDataCompany();
        }

        // nezaškrtl, že chce zadat poznámku
        if (!$order->isNoteChecked())
        {
            $order->setNote(null);
        }

        // nezaškrtl, že chce zadat jinou fakturační adresu, takže se nastaví na hodnoty doručovací
        if (!$order->isBillingAddressChecked())
        {
            $order->resetAddressBilling();
            $order->loadAddressBillingFromDelivery();
        }

        // odečtení počtu produktů na skladě
        /** @var CartOccurence $cartOccurence */
        foreach ($order->getCartOccurences() as $cartOccurence)
        {
            $product = $cartOccurence->getProduct();
            $productInventory = $product->getInventory();
            $cartOccurenceQuantity = $cartOccurence->getQuantity();
            $product->setInventory($productInventory - $cartOccurenceQuantity);
        }

        /** @var User|null $user */
        $user = $this->security->getUser();
        $order->setUser($user);

        $order->setToken(Uuid::v4());
        $order->setFinishedAt(new DateTime('now'));

        return $this;
    }

    /**
     * Nastaví objednávku do zrušeného stavu. Neřeší ukládání.
     *
     * @param Order $order
     * @param bool $forceInventoryReplenish
     * @return $this
     */
    public function cancelOrder(Order $order, bool $forceInventoryReplenish): self
    {
        $order->setLifecycleChapter(Order::LIFECYCLE_CANCELLED);

        // přidání počtu produktů zpět na sklad
        /** @var CartOccurence $cartOccurence */
        foreach ($order->getCartOccurences() as $cartOccurence)
        {
            if ($forceInventoryReplenish || $cartOccurence->isMarkedForInventoryReplenishment())
            {
                $product = $cartOccurence->getProduct();
                $productInventory = $product->getInventory();
                $cartOccurenceQuantity = $cartOccurence->getQuantity();
                $product->setInventory($productInventory + $cartOccurenceQuantity);
            }
        }

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