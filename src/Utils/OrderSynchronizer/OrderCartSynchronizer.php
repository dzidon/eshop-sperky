<?php

namespace App\Utils\OrderSynchronizer;

class OrderCartSynchronizer extends AbstractOrderSynchronizer
{
    protected static function getWarningPrefix(): string
    {
        return 'Ve vašem košíku došlo ke změně: ';
    }

    public function synchronize(): void
    {
        // pocet ks produktu - warning
        // ze ma cartoccurence prirazenou prave jednu produktovou volbu z kazde skupiny prod voleb produktu - warning
        // cena zvoleneho zpusobu dopravy - warning
        // cena zvoleneho zpusobu platby - warning

        $productsChangedPrice = [];

        foreach ($this->order->getCartOccurences() as $cartOccurence)
        {
            $product = $cartOccurence->getProduct();

            // nazev produktu - warning neni potreba, tohle neni tak dulezita zmena
            if ($product->getName() !== null && $cartOccurence->getName() !== $product->getName())
            {
                $this->orderChanged = true;
                $cartOccurence->setName($product->getName());
            }

            // ceny produktu
            if (($product->getPriceWithoutVat() !== null && $cartOccurence->getPriceWithoutVat() !== $product->getPriceWithoutVat())
             || ($product->getPriceWithVat() !== null && $cartOccurence->getPriceWithVat() !== $product->getPriceWithVat()))
            {
                $this->orderChanged = true;
                $cartOccurence->setPriceWithoutVat($product->getPriceWithoutVat());
                $cartOccurence->setPriceWithVat($product->getPriceWithVat());

                if(!isset($productsChangedPrice[$product->getId()]))
                {
                    $this->addWarning(sprintf('Cena produktu "%s" je nyní %.2f Kč vč. DPH (%.2f Kč bez DPH).', $product->getName(), $product->getPriceWithVat(), $product->getPriceWithoutVat()));
                    $productsChangedPrice[$product->getId()] = true;
                }
            }
        }
    }
}