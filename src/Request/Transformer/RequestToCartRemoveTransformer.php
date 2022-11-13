<?php

namespace App\Request\Transformer;

use App\Entity\Detached\CartRemove;
use App\Exception\RequestTransformerException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

class RequestToCartRemoveTransformer extends AbstractRequestTransformer
{
    private CsrfTokenManagerInterface $csrfTokenManager;

    public function __construct(CsrfTokenManagerInterface $csrfTokenManager)
    {
        $this->csrfTokenManager = $csrfTokenManager;
    }

    /**
     * @param Request $request
     * @return CartRemove
     * @throws RequestTransformerException
     */
    public function createCartRemove(Request $request): CartRemove
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

        $cartOccurenceId = $this->valueAsIntOrNull($cartRemoveVariables['cartOccurenceId']);

        return (new CartRemove())
            ->setCartOccurenceId($cartOccurenceId)
        ;
    }
}