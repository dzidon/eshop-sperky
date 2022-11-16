<?php

namespace App\Request\Transformer;

use App\Entity\Detached\CartUpdate;
use App\Entity\Order;
use App\Exception\RequestTransformerException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

/**
 * Služba pro převod HTTP požadavku na objekt třídy App\Entity\Detached\CartUpdate.
 *
 * @package App\Request\Transformer
 */
class RequestToCartUpdateTransformer extends AbstractRequestTransformer
{
    private CsrfTokenManagerInterface $csrfTokenManager;

    public function __construct(CsrfTokenManagerInterface $csrfTokenManager)
    {
        $this->csrfTokenManager = $csrfTokenManager;
    }

    /**
     * Z HTTP požadavku vytvoří třídu představující požadavek na aktualizaci košíku. POST musí obsahovat
     * pole na indexu 'cart_form'. Toto pole má následující tvar:
     *
     * 'cartOccurences' => [
     *      {id výskytu v košíku} => [
     *          'quantity' => {počet ks}
     *      ],
     *      {id dalšího výskytu v košíku} => [
     *          'quantity' => {počet ks}
     *      ],
     *      ...
     * ],
     * '_token' => {token}
     *
     * CSRF token je nutné vytvořit pomocí klíče 'form_cart_update'.
     *
     * @param Request $request
     * @param Order $order
     * @return CartUpdate
     * @throws RequestTransformerException
     */
    public function createCartUpdate(Request $request, Order $order): CartUpdate
    {
        $requestVariables = $request->request->all();
        if (!array_key_exists('cart_form', $requestVariables))
        {
            throw new RequestTransformerException('V požadavku chybí data potřebná pro aktualizaci košíku.');
        }

        $cartUpdateVariables = $requestVariables['cart_form'];
        if (!array_key_exists('_token', $cartUpdateVariables))
        {
            throw new RequestTransformerException('V požadavku chybí data potřebná pro aktualizaci košíku.');
        }

        if (!$this->csrfTokenManager->isTokenValid(new CsrfToken('form_cart_update', $cartUpdateVariables['_token'])))
        {
            throw new RequestTransformerException('Váš CSRF token není platný. Aktualizujte stránku.');
        }

        if (!array_key_exists('cartOccurences', $cartUpdateVariables))
        {
            $cartUpdateVariables['cartOccurences'] = [];
        }

        $cartUpdate = new CartUpdate();
        $cartOccurencesInput = $this->valueAsArray($cartUpdateVariables['cartOccurences']);

        foreach ($cartOccurencesInput as $cartOccurenceId => $input)
        {
            if (!array_key_exists('quantity', $input))
            {
                continue;
            }

            $quantity = $this->valueAsIntOrNull($input['quantity']);

            $clonedCartOccurence = clone $order->getCartOccurences()->get($cartOccurenceId);
            $clonedCartOccurence->setQuantity($quantity);
            $cartUpdate
                ->getCartOccurences()
                ->set($cartOccurenceId, $clonedCartOccurence)
            ;
        }

        return $cartUpdate;
    }
}