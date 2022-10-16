<?php

namespace App\Facade;

use App\Entity\CartOccurence;
use App\Entity\Order;
use App\Entity\PaymentMethod;
use App\Entity\User;
use App\Service\CartService;
use App\Service\OrderEmailService;
use App\Service\OrderSynchronizer;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Uid\Uuid;

/**
 * Třída manipulující s objednávkou.
 *
 * @package App\Facade
 */
class OrderFacade
{
    private Security $security;
    private LoggerInterface $logger;
    private RouterInterface $router;
    private OrderEmailService $orderEmailService;
    private OrderSynchronizer $synchronizer;
    private EntityManagerInterface $entityManager;

    public function __construct(Security $security, LoggerInterface $logger, RouterInterface $router, OrderEmailService $orderEmailService, OrderSynchronizer $synchronizer, EntityManagerInterface $entityManager)
    {
        $this->logger = $logger;
        $this->router = $router;
        $this->security = $security;
        $this->orderEmailService = $orderEmailService;
        $this->synchronizer = $synchronizer;
        $this->entityManager = $entityManager;
    }

    /**
     * Načte objednávku vytvořenou na míru podle tokenu.
     *
     * @param string|null $token
     * @return Order|null
     */
    public function loadCustomOrder(?string $token): ?Order
    {
        if ($token !== null && UUid::isValid($token))
        {
            $uuid = Uuid::fromString($token);

            /** @var Order|null $order */
            $order = $this->entityManager->getRepository(Order::class)->findOneAndFetchEverything($uuid);
            if ($order !== null && $order->isCreatedManually() && $order->getLifecycleChapter() === Order::LIFECYCLE_FRESH)
            {
                $warnings = $this->synchronizer->synchronize($order, false, 'Ve vaší objednávce na míru došlo ke změně: ');
                if ($order->hasSynchronizationWarnings())
                {
                    $this->synchronizer->addWarningsToFlashBag($warnings);
                    $this->entityManager->persist($order);
                    $this->entityManager->flush();
                }

                return $order;
            }
        }

        return null;
    }

    /**
     * Nastaví objednávku do dokončeného stavu. Pošle e-mail informující o stavu objednávky.
     * Vrátí odpověď pro přesměrování. Persistne objednávku a může rovnou i flushnout.
     *
     * @param Order $order
     * @param bool $flush
     * @return RedirectResponse
     */
    public function finishOrder(Order $order, bool $flush): RedirectResponse
    {
        $payment = $order->getPayment();

        $order->setLifecycleChapter(Order::LIFECYCLE_AWAITING_PAYMENT);

        // nastavení částky dobírky + objednávka na dobírku bude rovnou připravená na odeslání
        if ($order->getPaymentMethod() !== null && $order->getPaymentMethod()->getType() === PaymentMethod::TYPE_ON_DELIVERY)
        {
            $cashOnDelivery = $order->getTotalPriceWithVat(true);
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

        // persist & flush?
        $this->entityManager->persist($order);
        if ($flush)
        {
            $this->entityManager->flush();
        }

        // email
        $this->sendInfoEmail($order);

        // odpověď pro přesměrování
        if ($payment !== null && $payment->getGateUrl() !== null)
        {
            $url = $payment->getGateUrl();
        }
        else
        {
            $url = $this->router->generate('home');
        }

        $redirectResponse = new RedirectResponse($url);
        if (!$order->isCreatedManually())
        {
            $redirectResponse->headers->clearCookie(CartService::COOKIE_NAME);
        }

        return $redirectResponse;
    }

    /**
     * Nastaví objednávku do zrušeného stavu. Může vrátit počet ks produktů do skladu. Pošle e-mail informující o
     * stavu objednávky. Persistne objednávku a může rovnou i flushnout.
     *
     * @param Order $order
     * @param bool $forceInventoryReplenish
     * @param bool $flush
     * @return $this
     */
    public function cancelOrder(Order $order, bool $forceInventoryReplenish, bool $flush): self
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

        // email
        $this->sendInfoEmail($order);

        // persist & flush?
        $this->entityManager->persist($order);
        if ($flush)
        {
            $this->entityManager->flush();
        }

        return $this;
    }

    /**
     * Pošle e-mail informující o stavu objednávky.
     *
     * @param Order $order
     * @return $this
     */
    public function sendInfoEmail(Order $order): self
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
}