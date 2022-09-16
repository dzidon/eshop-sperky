<?php

namespace App\Controller\User;

use App\Entity\Detached\CartInsert;
use App\Entity\Product;
use App\Exception\CartException;
use App\Form\FormType\User\CartInsertFormType;
use App\Form\FormType\User\CartFormType;
use App\Response\Json;
use App\Service\CartService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/kosik", methods={"POST"})
 */
class CartController extends AbstractController
{
    private CartService $cart;

    public function __construct(CartService $cart)
    {
        $this->cart = $cart;
    }

    private function getProductIdFromRequest(Request $request): ?int
    {
        if(isset($request->request->all()['cart_insert_form']['productId']) && is_numeric($request->request->all()['cart_insert_form']['productId']))
        {
            return (int) $request->request->all()['cart_insert_form']['productId'];
        }

        return null;
    }

    private function getCartOccurenceIdFromRequest(Request $request): ?int
    {
        $cartOccurenceId = $request->request->get('cartOccurenceId');
        if($cartOccurenceId !== null)
        {
            return (int) $cartOccurenceId;
        }

        return null;
    }

    /**
     * @Route("/vlozit", name="cart_insert")
     */
    public function insert(Request $request): Response
    {
        if (!$request->isXmlHttpRequest())
        {
            throw $this->createNotFoundException();
        }

        $jsonResponse = new Json();
        $productId = $this->getProductIdFromRequest($request);
        if ($productId === null)
        {
            $jsonResponse->addResponseError('Bylo odesláno neplatné ID produktu. Zkuste aktualizovat stránku a opakovat akci.');
            return $jsonResponse->create();
        }

        /** @var Product|null $product */
        $product = $this->getDoctrine()->getRepository(Product::class)->findOneForCartInsert($productId);
        if ($product === null)
        {
            $jsonResponse->addResponseError('Tento produkt už nejde vložit do košíku.');
            return $jsonResponse->create();
        }

        $cartInsertRequest = new CartInsert($product);
        $form = $this->createForm(CartInsertFormType::class, $cartInsertRequest);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid())
        {
            try
            {
                $this->cart->insertProduct($cartInsertRequest->getProduct(), $cartInsertRequest->getQuantity(), $cartInsertRequest->getOptionGroups());
                $jsonResponse->setResponseHtml(
                    $this->renderView('fragments/_cart_insert_modal_content.html.twig', [
                        'product'           => $product,
                        'order'             => $this->cart->getOrder(),
                        'submittedQuantity' => $cartInsertRequest->getQuantity(),
                    ])
                )
                ->setResponseData('totalProducts', $this->cart->getOrder()->getTotalQuantity());
            }
            catch (CartException $exception)
            {
                $jsonResponse->addResponseError($exception->getMessage());
            }
        }
        else if ($form->isSubmitted() && !$form->isValid())
        {
            $jsonResponse->addResponseFormErrors($form);
        }

        return $jsonResponse->create();
    }

    /**
     * @Route("/aktualizovat", name="cart_update")
     */
    public function update(Request $request): Response
    {
        if (!$request->isXmlHttpRequest())
        {
            throw $this->createNotFoundException();
        }

        $jsonResponse = new Json();
        $order = $this->cart->getOrder();

        $form = $this->createForm(CartFormType::class, $order);
        $jsonResponse->setResponseHtml($this->renderView('fragments/forms_unique/_form_cart_update.html.twig', ['cartForm' => $form->createView(), 'order' => $order]));
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid())
        {
            $this->cart->updateQuantities();
            $form = $this->createForm(CartFormType::class, $order);
            $jsonResponse->setResponseHtml($this->renderView('fragments/forms_unique/_form_cart_update.html.twig', ['cartForm' => $form->createView(), 'order' => $order]));
        }
        else if ($form->isSubmitted() && !$form->isValid())
        {
            $jsonResponse->addResponseFormErrors($form);
        }

        return $jsonResponse
            ->setResponseData('flashHtml', $this->renderView('fragments/_flash_messages.html.twig'))
            ->setResponseData('totalProducts', $order->getTotalQuantity())
            ->create()
        ;
    }

    /**
     * @Route("/smazat", name="cart_remove")
     */
    public function remove(Request $request): Response
    {
        if (!$request->isXmlHttpRequest())
        {
            throw $this->createNotFoundException();
        }

        $jsonResponse = new Json();
        $cartOccurenceId = $this->getCartOccurenceIdFromRequest($request);

        try
        {
            $this->cart->removeCartOccurence($cartOccurenceId);
        }
        catch (CartException $exception)
        {
            $jsonResponse->addResponseError($exception->getMessage());
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