<?php

namespace App\Service;

use App\Entity\Order;
use App\Messenger\OrderSynchronizationWarningsMessenger;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Synchronizátor zajišťuje aktuálnost historických dat objednávky.
 *
 * @package App\Service
 */
class OrderSynchronizer
{
    /**
     * Před vyvoláním těchto cest dojde k úplnému načtení a synchronizaci košíku.
     * Na jiných cestách stačí načítat počet produktů pro zobrazení v navbaru.
     */
    public const SYNCHRONIZATION_ROUTES = [
        'cart_insert' => true,
        'cart_update' => true,
        'cart_remove' => true,
        'order_cart' => true,
        'order_methods' => true,
        'order_addresses' => true,
    ];

    private RequestStack $requestStack;

    public function __construct(RequestStack $requestStack)
    {
        $this->requestStack = $requestStack;
    }

    /**
     * Vytvoří flash messages podle varování vzniklých při synchronizaci objednávky.
     *
     * @param OrderSynchronizationWarningsMessenger $warnings
     * @return void
     */
    public function addWarningsToFlashBag(OrderSynchronizationWarningsMessenger $warnings): void
    {
        $request = $this->requestStack->getCurrentRequest();

        foreach ($warnings->getWarnings() as $warning)
        {
            $warningsPrefix = $warnings->getMessagePrefix();
            $request->getSession()->getFlashBag()->add('warning', $warningsPrefix . $warning);
        }
    }

    /**
     * Porovná hodnoty historických dat objednávky se stavem souvisejících entit, např. celkovou cenu objednávky (ta
     * závisí na aktuální ceně produktů). Pokud historická data nesedí, dojde k aktualizaci a vytvoření varování.
     *
     * @param Order $order
     * @param bool $withCartOccurences
     * @param string $warningPrefix
     * @return OrderSynchronizationWarningsMessenger Kolekce všech vzniklých varování (1 změna objednávky = 1 varování)
     */
    public function synchronize(Order $order, bool $withCartOccurences, string $warningPrefix = ''): OrderSynchronizationWarningsMessenger
    {
        $warnings = new OrderSynchronizationWarningsMessenger($warningPrefix);
        $order->setHasSynchronizationWarnings(false);

        // cena doručovací metody
        $deliveryMethod = $order->getDeliveryMethod();
        if ($deliveryMethod !== null)
        {
            if (($order->getDeliveryPriceWithoutVat() !== $deliveryMethod->getPriceWithoutVat())
              || $order->getDeliveryPriceWithVat()    !== $deliveryMethod->getPriceWithVat())
            {
                $order->setDeliveryPriceWithoutVat( $deliveryMethod->getPriceWithoutVat() );
                $order->setDeliveryPriceWithVat( $deliveryMethod->getPriceWithVat() );

                $order->setHasSynchronizationWarnings(true);
                $warnings->addWarning('delivery_method_price', sprintf('Cena doručovací metody "%s" je nyní %.2f Kč vč. DPH (%.2f Kč bez DPH).', $deliveryMethod->getName(), $order->getDeliveryPriceWithVat(), $order->getDeliveryPriceWithoutVat()));
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

                $order->setHasSynchronizationWarnings(true);
                $warnings->addWarning('payment_method_price', sprintf('Cena platební metody "%s" je nyní %.2f Kč vč. DPH (%.2f Kč bez DPH).', $paymentMethod->getName(), $order->getPaymentPriceWithVat(), $order->getPaymentPriceWithoutVat()));
            }
        }

        if ($withCartOccurences)
        {
            $productsTotalQuantity = [];

            foreach ($order->getCartOccurences() as $cartOccurence)
            {
                $product = $cartOccurence->getProduct();

                if ($product === null || !$product->isVisible())
                {
                    $order->removeCartOccurence($cartOccurence);

                    $order->setHasSynchronizationWarnings(true);
                    $warnings->addWarning(
                        sprintf('productnull_%s', $cartOccurence->getName()),
                        sprintf('Produkt "%s" byl odstraněn, protože přestal existovat v katalogu.', $cartOccurence->getName())
                    );
                }
                else
                {
                    // vložený počet ks je 0
                    if($cartOccurence->getQuantity() <= 0)
                    {
                        $order->removeCartOccurence($cartOccurence);

                        $order->setHasSynchronizationWarnings(true);
                        $warnings->addWarning(
                            sprintf('quantityzero_%d', $product->getId()),
                            sprintf('Produkt "%s" byl odstraněn, protože měl nastavený počet kusů na 0.', $cartOccurence->getName())
                        );

                        continue;
                    }

                    // počet ks na skladě
                    if (!isset($productsTotalQuantity[$product->getId()]))
                    {
                        $productsTotalQuantity[$product->getId()] = 0;
                    }

                    if ($productsTotalQuantity[$product->getId()]+$cartOccurence->getQuantity() > $product->getInventory())
                    {
                        $order->removeCartOccurence($cartOccurence);

                        $order->setHasSynchronizationWarnings(true);
                        $warnings->addWarning(
                            sprintf('quantity_%d', $product->getId()),
                            sprintf('Produkt "%s" byl odstraněn, protože už nemáme tolik kusů na skladě.', $cartOccurence->getName())
                        );

                        continue;
                    }
                    $productsTotalQuantity[$product->getId()] += $cartOccurence->getQuantity();

                    // produktove volby, jejichz skupina neni prirazena danemu produktu
                    foreach ($cartOccurence->getOptions() as $option)
                    {
                        $cartOccurenceOptionGroup = $option->getProductOptionGroup();
                        if(!$product->getOptionGroups()->contains($cartOccurenceOptionGroup))
                        {
                            $cartOccurence->removeOption($option);
                        }
                    }

                    // hodnoty voleb
                    $oldOptionsString = $cartOccurence->getOptionsString();
                    $cartOccurence->generateOptionsString();
                    if ($oldOptionsString !== $cartOccurence->getOptionsString())
                    {
                        $order->setHasSynchronizationWarnings(true);
                        $warnings->addWarning(
                            sprintf('optionsstring_%d', $cartOccurence->getId()),
                            sprintf('Hodnoty voleb produktu "%s" se změnily z "%s" na "%s".', $cartOccurence->getName(), $oldOptionsString, $cartOccurence->getOptionsString())
                        );
                    }

                    // ceny produktu
                    if (($product->getPriceWithoutVat() !== null && $cartOccurence->getPriceWithoutVat() !== $product->getPriceWithoutVat())
                        || ($product->getPriceWithVat() !== null && $cartOccurence->getPriceWithVat() !== $product->getPriceWithVat()))
                    {
                        $cartOccurence->setPriceWithoutVat($product->getPriceWithoutVat());
                        $cartOccurence->setPriceWithVat($product->getPriceWithVat());

                        $order->setHasSynchronizationWarnings(true);
                        $warnings->addWarning(
                            sprintf('price_%d', $product->getId()),
                            sprintf('Cena produktu "%s" je nyní %.2f Kč vč. DPH (%.2f Kč bez DPH).', $cartOccurence->getName(), $cartOccurence->getPriceWithVat(), $cartOccurence->getPriceWithoutVat())
                        );
                    }
                }
            }
        }

        return $warnings;
    }
}