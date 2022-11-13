<?php

namespace App\Request\Transformer;

use App\Entity\Detached\CartInsert;
use App\Entity\Product;
use App\Entity\ProductOption;
use App\Exception\RequestTransformerException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

/**
 * Služba pro převod HTTP požadavku na objekt třídy App\Entity\Detached\CartInsert.
 *
 * @package App\Request\Transformer
 */
class RequestToCartInsertTransformer extends AbstractRequestTransformer
{
    private EntityManagerInterface $entityManager;
    private CsrfTokenManagerInterface $csrfTokenManager;

    public function __construct(EntityManagerInterface $entityManager, CsrfTokenManagerInterface $csrfTokenManager)
    {
        $this->entityManager = $entityManager;
        $this->csrfTokenManager = $csrfTokenManager;
    }

    /**
     * Z HTTP požadavku vytvoří třídu představující požadavek na vložení produktu do košíku. POST musí obsahovat
     * pole na indexu 'cart_insert_form'. Toto pole má následující tvar:
     *
     * 'productId'      - ID vkládaného produktu
     * '_token'         - CSRF token vytvořený pomocí klíče 'form_cart_insert'
     * 'quantity'       - Počet ks (VOLITELNÉ, výchozí hodnota: 1)
     * 'optionGroups'   - Produktové volby, pole ve tvaru 'ID skupiny' => 'ID volby' (VOLITELNÉ, výchozí hodnota: [])
     *
     * @param Request $request
     * @return CartInsert
     * @throws RequestTransformerException
     */
    public function createCartInsert(Request $request): CartInsert
    {
        $requestVariables = $request->request->all();
        if (!array_key_exists('cart_insert_form', $requestVariables))
        {
            throw new RequestTransformerException('V požadavku chybí data potřebná pro vložení produktu do košíku.');
        }

        $cartInsertVariables = $requestVariables['cart_insert_form'];
        if (!array_key_exists('productId', $cartInsertVariables) ||
            !array_key_exists('_token', $cartInsertVariables))
        {
            throw new RequestTransformerException('V požadavku chybí data potřebná pro vložení produktu do košíku.');
        }

        if (!$this->csrfTokenManager->isTokenValid(new CsrfToken('form_cart_insert', $cartInsertVariables['_token'])))
        {
            throw new RequestTransformerException('Váš CSRF token není platný. Aktualizujte stránku.');
        }

        if (!array_key_exists('quantity', $cartInsertVariables))
        {
            $cartInsertVariables['quantity'] = 1;
        }

        if (!array_key_exists('optionGroups', $cartInsertVariables))
        {
            $cartInsertVariables['optionGroups'] = [];
        }

        // productId na Product
        $productId = $this->valueAsIntOrNull($cartInsertVariables['productId']);

        /** @var Product|null $product */
        $product = $this->entityManager->getRepository(Product::class)->findOneForCartInsert($productId);

        // quantity na null/int
        $quantity = $this->valueAsIntOrNull($cartInsertVariables['quantity']);

        // optionGroups na null/array
        $optionGroups = $this->valueAsArray($cartInsertVariables['optionGroups']);

        if ($product !== null)
        {
            $productOptionGroups = $product->getOptionGroups();
            $productOptionGroupsIndex = [];

            foreach ($productOptionGroups as $productOptionGroup)
            {
                // indexace skupin a voleb
                foreach ($productOptionGroup->getOptions() as $productOption)
                {
                    $productOptionGroupsIndex[$productOptionGroup->getId()][$productOption->getId()] = $productOption;
                }

                // je mozne, ze uzivatel nejakou ze skupin produktovych voleb prirazenych produktu
                // neodesle, v takovem pripade se nastavi prvni volba pro kazdou skupinu

                /** @var ProductOption|false $firstOption */
                $firstOption = $productOptionGroup->getOptions()->first();
                if (!array_key_exists($productOptionGroup->getId(), $optionGroups) || $optionGroups[$productOptionGroup->getId()] === null)
                {
                    if ($firstOption instanceof ProductOption)
                    {
                        $optionGroups[$productOptionGroup->getId()] = $firstOption;
                    }
                    else
                    {
                        unset($optionGroups[$productOptionGroup->getId()]);
                    }
                }
            }

            // převod id voleb na objekty
            foreach ($optionGroups as $optionGroupId => $optionId) // uzivatelsky vstup
            {
                // volba už byla nastavena na výchozí objekt, není třeba převádět
                if ($optionId instanceof ProductOption)
                {
                    continue;
                }

                // kontrola, zda je uživatelem odeslaná skupina přiřazená produktu
                if (!array_key_exists($optionGroupId, $productOptionGroupsIndex))
                {
                    throw new RequestTransformerException('Odeslaná skupina produktových voleb už neexistuje.');
                }

                // kontrola, zda ve skupině existuje požadovaná volba
                if (!array_key_exists($optionId, $productOptionGroupsIndex[$optionGroupId]))
                {
                    throw new RequestTransformerException('Odeslaná produktová volba už neexistuje.');
                }

                // id volby se nahradí za objekt
                $optionGroups[$optionGroupId] = $productOptionGroupsIndex[$optionGroupId][$optionId];
            }
        }

        return (new CartInsert())
            ->setProduct($product)
            ->setQuantity($quantity)
            ->setOptionGroups($optionGroups)
        ;
    }
}