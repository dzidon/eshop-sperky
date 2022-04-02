<?php

namespace App\Service\OrderSynchronizer;

/**
 * Synchronizuje stav aktivní objednávky v košíku se stavem ostatních entit
 *
 * @package App\Utils\OrderSynchronizer
 */
class OrderCartSynchronizer extends AbstractOrderSynchronizer
{
    /**
     * {@inheritdoc}
     */
    protected static function getWarningPrefix(): string
    {
        return 'Ve vašem košíku došlo ke změně: ';
    }

    /**
     * {@inheritdoc}
     */
    public function synchronize(): void
    {
        parent::synchronize();

        $productsTotalQuantity = [];

        foreach ($this->order->getCartOccurences() as $cartOccurence)
        {
            $product = $cartOccurence->getProduct();

            if ($product === null || !$product->isVisible())
            {
                $this->order->removeCartOccurence($cartOccurence);
                $this->addWarning(
                    sprintf('productnull_%s', $cartOccurence->getName()),
                    sprintf('Produkt "%s" byl odstraněn, protože přestal existovat v katalogu.', $cartOccurence->getName())
                );
            }
            else
            {
                // vložený počet ks je 0
                if($cartOccurence->getQuantity() <= 0)
                {
                    $this->order->removeCartOccurence($cartOccurence);
                    $this->addWarning(
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
                    $this->order->removeCartOccurence($cartOccurence);
                    $this->addWarning(
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
                    $this->addWarning(
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

                    $this->addWarning(
                        sprintf('price_%d', $product->getId()),
                        sprintf('Cena produktu "%s" je nyní %.2f Kč vč. DPH (%.2f Kč bez DPH).', $cartOccurence->getName(), $cartOccurence->getPriceWithVat(), $cartOccurence->getPriceWithoutVat())
                    );
                }
            }
        }
    }
}