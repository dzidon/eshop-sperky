<?php

namespace App\Controller\User;

use App\Entity\Detached\CartInsert;
use App\Entity\Product;
use App\Exception\CartException;
use App\Form\CartFormType;
use App\Form\CartInsertFormType;
use App\Response\Json;
use App\Service\CartService;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/kosik", methods={"POST"})
 */
class CartController extends AbstractController
{
    private $request;
    private CartService $cart;
    private LoggerInterface $logger;

    public function __construct(LoggerInterface $logger, RequestStack $requestStack, CartService $cart)
    {
        $this->cart = $cart;
        $this->logger = $logger;
        $this->request = $requestStack->getCurrentRequest();
    }

    private function getProductIdFromRequest(string $formName): ?int
    {
        if(isset($this->request->request->all()[$formName]['productId']) && is_numeric($this->request->request->all()[$formName]['productId']))
        {
            return (int) $this->request->request->all()[$formName]['productId'];
        }

        return null;
    }

    private function getCartOccurenceIdFromRequest(): ?int
    {
        $cartOccurenceId = $this->request->request->get('cartOccurenceId');
        if($cartOccurenceId !== null)
        {
            return (int) $cartOccurenceId;
        }

        return null;
    }

    /**
     * @Route("/vlozit", name="cart_insert")
     */
    public function insert(): Response
    {
        if (!$this->request->isXmlHttpRequest())
        {
            throw $this->createNotFoundException();
        }

        $jsonResponse = new Json();
        $productId = $this->getProductIdFromRequest('cart_insert_form');
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
        $form->handleRequest($this->request);

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
    public function update(): Response
    {
        if (!$this->request->isXmlHttpRequest())
        {
            throw $this->createNotFoundException();
        }

        $jsonResponse = new Json();
        $order = $this->cart->getOrder();

        $form = $this->createForm(CartFormType::class, $order);
        $jsonResponse->setResponseHtml($this->renderView('fragments/forms_unique/_form_cart_update.html.twig', ['cartForm' => $form->createView(), 'order' => $order]));
        $form->handleRequest($this->request);

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
    public function remove(): Response
    {
        if (!$this->request->isXmlHttpRequest())
        {
            throw $this->createNotFoundException();
        }

        $jsonResponse = new Json();
        $cartOccurenceId = $this->getCartOccurenceIdFromRequest();

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