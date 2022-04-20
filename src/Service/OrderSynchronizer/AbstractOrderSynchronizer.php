<?php

namespace App\Service\OrderSynchronizer;

use App\Entity\Order;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Abstraktní třída pro synchronizátory, které zajišťují aktuálnost stavu objednávky.
 *
 * @package App\Utils\OrderSynchronizer
 */
abstract class AbstractOrderSynchronizer
{
    protected bool $hasWarnings = false;
    private array $warnings = [];

    private $request;

    public function __construct(RequestStack $requestStack)
    {
        $this->request = $requestStack->getCurrentRequest();
    }

    /**
     * Synchronizuje stav objednávky.
     *
     * @param Order $order
     */
    public function synchronize(Order $order): void
    {
        // cena doručovací metody
        $deliveryMethod = $order->getDeliveryMethod();
        if ($deliveryMethod !== null)
        {
            if (($order->getDeliveryPriceWithoutVat() !== $deliveryMethod->getPriceWithoutVat())
              || $order->getDeliveryPriceWithVat()    !== $deliveryMethod->getPriceWithVat())
            {
                $order->setDeliveryPriceWithoutVat( $deliveryMethod->getPriceWithoutVat() );
                $order->setDeliveryPriceWithVat( $deliveryMethod->getPriceWithVat() );

                $this->addWarning('delivery_method_price', sprintf('Cena doručovací metody "%s" je nyní %.2f Kč vč. DPH (%.2f Kč bez DPH).', $deliveryMethod->getName(), $order->getDeliveryPriceWithVat(), $order->getDeliveryPriceWithoutVat()));
            }
        }

        // cena platební metody
        $paymentMethod = $order->getPaymentMethod();
        if ($paymentMethod !== null)
        {
            if (($order->getPaymentPriceWithoutVat() !== $paymentMethod->getPriceWithoutVat())
              || $order->getPaymentPriceWithVat()    !== $paymentMethod->getPriceWithVat())
            {
                $order->setPaymentPriceWithoutVat( $paymentMethod->getPriceWithoutVat() );
                $order->setPaymentPriceWithVat( $paymentMethod->getPriceWithVat() );

                $this->addWarning('payment_method_price', sprintf('Cena platební metody "%s" je nyní %.2f Kč vč. DPH (%.2f Kč bez DPH).', $paymentMethod->getName(), $order->getPaymentPriceWithVat(), $order->getPaymentPriceWithoutVat()));
            }
        }
    }

    /**
     * @return bool
     */
    public function hasWarnings(): bool
    {
        return $this->hasWarnings;
    }

    /**
     * Přidá varování do flash bagu
     */
    public function addWarningsToFlashBag(): void
    {
        if($this->hasWarnings)
        {
            foreach ($this->warnings as $warning)
            {
                $this->request->getSession()->getFlashBag()->add('warning', $warning);
            }
        }
    }

    /**
     * Přidá varování vzniklé během synchronizace.
     *
     * @param string $warningKey
     * @param string $warningText
     * @return $this
     */
    protected function addWarning(string $warningKey, string $warningText): self
    {
        $this->warnings[$warningKey] = static::getWarningPrefix() . $warningText;
        $this->hasWarnings = true;

        return $this;
    }

    /**
     * Vrátí prefix pro varování vzniklé při synchronizaci.
     *
     * @return string
     */
    abstract protected static function getWarningPrefix(): string;
}