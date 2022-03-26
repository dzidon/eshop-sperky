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

        // TODO: cena zvoleneho zpusobu dopravy
        // TODO: cena zvoleneho zpusobu platby

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
                // počet ks na skladě
                if (!isset($productsTotalQuantity[$product->getId()]))
                {
                    $productsTotalQuantity[$product->getId()] = 0;
                }

                if ($productsTotalQuantity[$product->getId()]+$cartOccurence->getQuantity() > $product->getInventory())
                {
                    $this->order->removeCartOccurence($cartOccurence);
                    $this->addWarning(
                        sprintf('quantity_%s', $cartOccurence->getName()),
                        sprintf('Produkt "%s" byl odstraněn, protože už nemáme tolik kusů na skladě.', $cartOccurence->getName())
                    );

                    continue;
                }
                $productsTotalQuantity[$product->getId()] += $cartOccurence->getQuantity();

                // nazev produktu
                if ($product->getName() !== null && $cartOccurence->getName() !== $product->getName())
                {
                    $this->addWarning(
                        sprintf('name_%s', $cartOccurence->getName()),
                        sprintf('Název produktu "%s" je nyní "%s".', $cartOccurence->getName(), $product->getName())
                    );

                    $cartOccurence->setName($product->getName());
                }

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
                        sprintf('price_%s', $cartOccurence->getName()),
                        sprintf('Cena produktu "%s" je nyní %.2f Kč vč. DPH (%.2f Kč bez DPH).', $cartOccurence->getName(), $cartOccurence->getPriceWithVat(), $cartOccurence->getPriceWithoutVat())
                    );
                }
            }
        }
    }
}