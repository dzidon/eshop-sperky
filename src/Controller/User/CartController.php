<?php

namespace App\Controller\User;

use App\Entity\Detached\CartInsert;
use App\Entity\Product;
use App\Form\CartInsertFormType;
use App\Service\CartService;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
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

    public function __construct(LoggerInterface $logger, RequestStack $requestStack, CartService $cart)
    {
        $this->cart = $cart;
        $this->logger = $logger;
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

        $responseData = [
            'errors' => [],
        ];

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
                if(true/*$this->cart->insertProduct($cartInsertRequest->getProduct(), $cartInsertRequest->getQuantity())*/)
                {
                    $responseData['errors'][] = $cartInsertRequest->getProduct()->getName();
                }
                else
                {
                    $responseData['errors'][] = sprintf('Máme na skladě jen %s ks.', $product->getInventory());
                }
            }
            else
            {
                foreach ($form->getErrors() as $formError)
                {
                    $responseData['errors'][] = $formError->getMessage();
                }
            }
        }
        else
        {
            $responseData['errors'][] = 'Tento produkt už nejde vložit do košíku.';
        }

        return new JsonResponse($responseData);
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