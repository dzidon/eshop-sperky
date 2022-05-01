<?php

namespace App\OrderSynchronizer;

use App\Entity\Order;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Abstraktní třída pro synchronizátory, které zajišťují aktuálnost stavu objednávky.
 *
 * @package App\OrderSynchronizer
 */
abstract class AbstractOrderSynchronizer
{
    private RequestStack $requestStack;

    protected Order $order;
    private array $warnings = [];

    public function __construct(RequestStack $requestStack)
    {
        $this->requestStack = $requestStack;
    }

    /**
     * Přidá varování do flash bagu
     *
     * @param Order $order
     */
    public function synchronizeAndAddWarningsToFlashBag(Order $order): void
    {
        $this->synchronize($order);

        if($this->order->hasSynchronizationWarnings())
        {
            $request = $this->requestStack->getCurrentRequest();

            foreach ($this->warnings as $warning)
            {
                $request->getSession()->getFlashBag()->add('warning', $warning);
            }
        }
    }

    /**
     * Synchronizuje stav objednávky.
     *
     * @param Order $order
     */
    protected function synchronize(Order $order): void
    {
        $this->order = $order;
        $this->order->setHasSynchronizationWarnings(false);
        $this->warnings = [];

        // cena doručovací metody
        $deliveryMethod = $this->order->getDeliveryMethod();
        if ($deliveryMethod !== null)
        {
            if (($this->order->getDeliveryPriceWithoutVat() !== $deliveryMethod->getPriceWithoutVat())
              || $this->order->getDeliveryPriceWithVat()    !== $deliveryMethod->getPriceWithVat())
            {
                $this->order->setDeliveryPriceWithoutVat( $deliveryMethod->getPriceWithoutVat() );
                $this->order->setDeliveryPriceWithVat( $deliveryMethod->getPriceWithVat() );

                $this->addWarning('delivery_method_price', sprintf('Cena doručovací metody "%s" je nyní %.2f Kč vč. DPH (%.2f Kč bez DPH).', $deliveryMethod->getName(), $this->order->getDeliveryPriceWithVat(), $this->order->getDeliveryPriceWithoutVat()));
            }
        }

        // cena platební metody
        $paymentMethod = $this->order->getPaymentMethod();
        if ($paymentMethod !== null)
        {
            if (($this->order->getPaymentPriceWithoutVat() !== $paymentMethod->getPriceWithoutVat())
              || $this->order->getPaymentPriceWithVat()    !== $paymentMethod->getPriceWithVat())
            {
                $this->order->setPaymentPriceWithoutVat( $paymentMethod->getPriceWithoutVat() );
                $this->order->setPaymentPriceWithVat( $paymentMethod->getPriceWithVat() );

                $this->addWarning('payment_method_price', sprintf('Cena platební metody "%s" je nyní %.2f Kč vč. DPH (%.2f Kč bez DPH).', $paymentMethod->getName(), $this->order->getPaymentPriceWithVat(), $this->order->getPaymentPriceWithoutVat()));
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
        $this->order->setHasSynchronizationWarnings(true);

        return $this;
    }

    /**
     * Vrátí prefix pro varování vzniklé při synchronizaci.
     *
     * @return string
     */
    abstract protected static function getWarningPrefix(): string;
}