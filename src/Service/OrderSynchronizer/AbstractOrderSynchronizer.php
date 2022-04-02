<?php

namespace App\Service\OrderSynchronizer;

use App\Entity\Order;
use LogicException;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Abstraktní třída pro synchronizátory, které zajišťují aktuálnost stavu objednávky.
 *
 * @package App\Utils\OrderSynchronizer
 */
abstract class AbstractOrderSynchronizer
{
    protected Order $order;
    protected bool $hasWarnings = false;
    private array $warnings = [];

    private $request;

    public function __construct(RequestStack $requestStack)
    {
        $this->request = $requestStack->getCurrentRequest();
    }

    /**
     * Nastaví objednávku, jejíž stav se má synchronizovat.
     *
     * @param Order $order
     * @return AbstractOrderSynchronizer
     */
    public function setOrder(Order $order): self
    {
        $this->order = $order;

        return $this;
    }

    /**
     * Synchronizuje stav objednávky.
     */
    public function synchronize(): void
    {
        if($this->order === null)
        {
            throw new LogicException( sprintf('%s nedostal objednávku přes setOrder.', static::class) );
        }

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