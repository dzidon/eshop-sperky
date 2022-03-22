<?php

namespace App\Controller\User;

use App\Entity\Detached\CartInsert;
use App\Entity\Product;
use App\Exception\CartException;
use App\Form\CartInsertFormType;
use App\Service\CartService;
use App\Service\JsonResponseService;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/kosik")
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

    /**
     * @Route("/vlozit", name="cart_insert", methods={"POST"})
     */
    public function insert(): Response
    {
        if (!$this->request->isXmlHttpRequest())
        {
            throw new NotFoundHttpException();
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
                    );
                }
                catch(CartException $exception)
                {
                    $this->jsonResponse->addResponseError($exception->getMessage());
                }
            }
            else
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

    private function getProductIdFromRequest(string $formName): string
    {
        $productId = '';
        if(isset($this->request->request->all()[$formName]['productId']))
        {
            $productId = $this->request->request->all()[$formName]['productId'];
        }

        return $productId;
    }
}