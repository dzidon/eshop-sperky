<?php

namespace App\Controller\User;

use App\Exception\CartException;
use App\Exception\RequestTransformerException;
use App\Form\FormType\User\CartFormType;
use App\Request\Transformer\RequestToCartInsertTransformer;
use App\Request\Transformer\RequestToCartRemoveTransformer;
use App\Request\Transformer\RequestToCartUpdateTransformer;
use App\Response\Json;
use App\Service\Cart;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/kosik", methods={"POST"}, condition="request.isXmlHttpRequest()")
 */
class CartController extends AbstractController
{
    private Cart $cart;

    public function __construct(Cart $cart)
    {
        $this->cart = $cart;
    }

    /**
     * @Route("/vlozit", name="cart_insert")
     */
    public function insert(Request $request, ValidatorInterface $validator, RequestToCartInsertTransformer $requestToCartInsertTransformer): Response
    {
        $jsonResponse = new Json();

        // prevod requestu a validace
        try
        {
            $cartInsertRequest = $requestToCartInsertTransformer->createCartInsert($request);
            $errors = $validator->validate($cartInsertRequest);
            if (count($errors) > 0)
            {
                return $jsonResponse->addResponseValidatorErrors($errors)->create();
            }
        }
        catch (RequestTransformerException $exception)
        {
            return $jsonResponse->addResponseError($exception->getMessage())->create();
        }

        // vlozeni do kosiku
        try
        {
            $this->cart->insertProduct($cartInsertRequest);
            $jsonResponse
                ->setResponseHtml(
                    $this->renderView('fragments/_cart_insert_modal_content.html.twig', [
                        'product'           => $cartInsertRequest->getProduct(),
                        'order'             => $this->cart->getOrder(),
                        'submittedQuantity' => $cartInsertRequest->getQuantity(),
                    ])
                )
                ->setResponseData('totalProducts', $this->cart->getOrder()->getTotalQuantity())
            ;
        }
        catch (CartException $exception)
        {
            $jsonResponse->addResponseError($exception->getMessage());
        }

        return $jsonResponse->create();
    }

    /**
     * @Route("/aktualizovat", name="cart_update")
     */
    public function update(Request $request, ValidatorInterface $validator, RequestToCartUpdateTransformer $requestToCartUpdateTransformer): Response
    {
        $jsonResponse = new Json();
        $order = $this->cart->getOrder();

        // prevod requestu a validace
        try
        {
            $cartUpdateRequest = $requestToCartUpdateTransformer->createCartUpdate($request, $order);
            $errors = $validator->validate($cartUpdateRequest);
            if (count($errors) > 0)
            {
                $jsonResponse->addResponseValidatorErrors($errors);
            }
        }
        catch (RequestTransformerException $exception)
        {
            $jsonResponse->addResponseError($exception->getMessage());
        }

        // aktualizace košíku
        if (!$jsonResponse->hasErrors())
        {
            $this->cart->updateQuantities($cartUpdateRequest);
        }

        $form = $this->createForm(CartFormType::class, $order);

        return $jsonResponse
            ->setResponseHtml($this->renderView('fragments/forms_unique/_form_cart_update.html.twig', ['cartForm' => $form->createView(), 'order' => $order]))
            ->setResponseData('flashHtml', $this->renderView('fragments/_flash_messages.html.twig'))
            ->setResponseData('totalProducts', $order->getTotalQuantity())
            ->create()
        ;
    }

    /**
     * @Route("/smazat", name="cart_remove")
     */
    public function remove(Request $request, ValidatorInterface $validator, RequestToCartRemoveTransformer $requestToCartRemoveTransformer): Response
    {
        $jsonResponse = new Json();
        $order = $this->cart->getOrder();

        // prevod requestu a validace
        try
        {
            $cartRemoveRequest = $requestToCartRemoveTransformer->createCartRemove($request, $order);
            $errors = $validator->validate($cartRemoveRequest);
            if (count($errors) > 0)
            {
                $jsonResponse->addResponseValidatorErrors($errors);
            }
        }
        catch (RequestTransformerException $exception)
        {
            $jsonResponse->addResponseError($exception->getMessage());
        }

        // odstraneni produktu z kosiku
        if (!$jsonResponse->hasErrors())
        {
            try
            {
                $this->cart->removeCartOccurence($cartRemoveRequest);
            }
            catch (CartException $exception)
            {
                $jsonResponse->addResponseError($exception->getMessage());
            }
        }

        $order = $this->cart->getOrder();
        $form = $this->createForm(CartFormType::class, $order);

        return $jsonResponse
            ->setResponseHtml($this->renderView('fragments/forms_unique/_form_cart_update.html.twig', ['cartForm' => $form->createView(), 'order' => $order]))
            ->setResponseData('flashHtml', $this->renderView('fragments/_flash_messages.html.twig'))
            ->setResponseData('totalProducts', $this->cart->getOrder()->getTotalQuantity())
            ->create()
        ;
    }
}