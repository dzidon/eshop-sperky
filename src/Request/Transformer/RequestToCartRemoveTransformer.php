<?php

namespace App\Request\Transformer;

use App\Entity\Detached\CartRemove;
use App\Entity\Order;
use App\Exception\RequestTransformerException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

/**
 * Služba pro převod HTTP požadavku na objekt třídy App\Entity\Detached\CartRemove.
 *
 * @package App\Request\Transformer
 */
class RequestToCartRemoveTransformer extends AbstractRequestTransformer
{
    private CsrfTokenManagerInterface $csrfTokenManager;

    public function __construct(CsrfTokenManagerInterface $csrfTokenManager)
    {
        $this->csrfTokenManager = $csrfTokenManager;
    }

    /**
     * Z HTTP požadavku vytvoří třídu představující požadavek na odstranění produktu z košíku. POST musí obsahovat
     * pole na indexu 'cart_remove_form'. Toto pole má následující tvar:
     *
     * 'cartOccurenceId'    - ID odstraňovaného výskytu v košíku
     * '_token'             - CSRF token vytvořený pomocí klíče 'form_cart_remove'
     *
     * @param Request $request
     * @param Order $order
     * @return CartRemove
     * @throws RequestTransformerException
     */
    public function createCartRemove(Request $request, Order $order): CartRemove
    {
        $requestVariables = $request->request->all();
        if (!array_key_exists('cart_remove_form', $requestVariables))
        {
            throw new RequestTransformerException('V požadavku chybí data potřebná pro odstranění produktu z košíku.');
        }

        $cartRemoveVariables = $requestVariables['cart_remove_form'];
        if (!array_key_exists('cartOccurenceId', $cartRemoveVariables) ||
            !array_key_exists('_token', $cartRemoveVariables))
        {
            throw new RequestTransformerException('V požadavku chybí data potřebná pro odstranění produktu z košíku.');
        }

        if (!$this->csrfTokenManager->isTokenValid(new CsrfToken('form_cart_remove', $cartRemoveVariables['_token'])))
        {
            throw new RequestTransformerException('Váš CSRF token není platný. Aktualizujte stránku.');
        }

        $targetCartOccurence = null;
        $targetCartOccurenceId = $this->valueAsIntOrNull($cartRemoveVariables['cartOccurenceId']);

        if ($targetCartOccurenceId !== null)
        {
            foreach ($order->getCartOccurences() as $cartOccurence)
            {
                if ($cartOccurence->getId() === $targetCartOccurenceId)
                {
                    $targetCartOccurence = clone $cartOccurence;
                    break;
                }
            }
        }

        return (new CartRemove())
            ->setCartOccurence($targetCartOccurence)
        ;
    }
}