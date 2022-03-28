<?php

namespace App\Controller\User;

use App\Entity\Detached\CartInsert;
use App\Entity\Product;
use App\Exception\CartException;
use App\Form\CartFormType;
use App\Form\CartInsertFormType;
use App\Service\CartService;
use App\Service\JsonResponseService;
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
    private JsonResponseService $jsonResponse;

    public function __construct(LoggerInterface $logger, RequestStack $requestStack, CartService $cart, JsonResponseService $jsonResponse)
    {
        $this->cart = $cart;
        $this->logger = $logger;
        $this->jsonResponse = $jsonResponse;
        $this->request = $requestStack->getCurrentRequest();
    }

    private function getProductIdFromRequest(string $formName): string
    {
        $productId = '';
        if(isset($this->request->request->all()[$formName]['productId']))
        {
            $productId = $this->request->request->all()[$formName]['productId'];
        }

        return $productId;
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

        $productId = $this->getProductIdFromRequest('cart_insert_form');
        /** @var Product|null $product */
        $product = $this->getDoctrine()->getRepository(Product::class)->findOneForCartInsert($productId);

        if($product !== null)
        {
            $cartInsertRequest = new CartInsert();
            $cartInsertRequest->setProduct($product);
            $form = $this->createForm(CartInsertFormType::class, $cartInsertRequest);
            $form->handleRequest($this->request);

            if ($form->isSubmitted() && $form->isValid())
            {
                try
                {
                    $this->cart->insertProduct($cartInsertRequest->getProduct(), $cartInsertRequest->getQuantity(), $cartInsertRequest->getOptionGroups());
                    $this->jsonResponse->setResponseHtml(
                        $this->renderView('fragments/_cart_insert_modal_content.html.twig', [
                            'cart'              => $this->cart,
                            'product'           => $cartInsertRequest->getProduct(),
                            'submittedQuantity' => $cartInsertRequest->getQuantity(),
                        ])
                    )
                    ->setResponseData('totalProducts', $this->cart->getTotalProducts());
                }
                catch(CartException $exception)
                {
                    $this->jsonResponse->addResponseError($exception->getMessage());
                }
            }
            else if ($form->isSubmitted() && !$form->isValid())
            {
                foreach ($form->getErrors() as $formError)
                {
                    $this->jsonResponse->addResponseError($formError->getMessage());
                }
            }
        }
        else
        {
            $this->jsonResponse->addResponseError('Tento produkt už nejde vložit do košíku.');
        }

        return $this->jsonResponse->createJsonResponse();
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

        $form = $this->createForm(CartFormType::class, $this->cart->getOrder());
        $this->jsonResponse->setResponseHtml($this->renderView('fragments/forms_unique/_form_cart_update.html.twig', ['cartForm' => $form->createView()]));
        $form->handleRequest($this->request);

        if ($form->isSubmitted())
        {
            if($form->isValid())
            {
                $this->cart->updateQuantities();
                $form = $this->createForm(CartFormType::class, $this->cart->getOrder());
                $this->jsonResponse->setResponseHtml($this->renderView('fragments/forms_unique/_form_cart_update.html.twig', ['cartForm' => $form->createView()]));
            }
            else
            {
                foreach ($form->getErrors() as $formError)
                {
                    $this->jsonResponse->addResponseError($formError->getMessage());
                }
            }
        }

        return $this->jsonResponse
            ->setResponseData('flashHtml', $this->renderView('fragments/_flash_messages.html.twig'))
            ->setResponseData('totalProducts', $this->cart->getTotalProducts())
            ->createJsonResponse()
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

        $cartOccurenceId = $this->getCartOccurenceIdFromRequest();

        try
        {
            $this->cart->removeCartOccurence($cartOccurenceId);
        }
        catch(CartException $exception)
        {
            $this->jsonResponse->addResponseError($exception->getMessage());
        }

        $form = $this->createForm(CartFormType::class, $this->cart->getOrder());

        return $this->jsonResponse
            ->setResponseHtml($this->renderView('fragments/forms_unique/_form_cart_update.html.twig', ['cartForm' => $form->createView()]))
            ->setResponseData('flashHtml', $this->renderView('fragments/_flash_messages.html.twig'))
            ->setResponseData('totalProducts', $this->cart->getTotalProducts())
            ->createJsonResponse()
        ;
    }
}