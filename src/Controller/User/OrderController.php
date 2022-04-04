<?php

namespace App\Controller\User;

use App\Form\CartFormType;
use App\Form\OrderMethodsFormType;
use App\Service\BreadcrumbsService;
use App\Service\CartService;
use App\Service\CustomOrderService;
use App\Service\JsonResponseService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class OrderController extends AbstractController
{
    private CartService $cart;
    private CustomOrderService $customOrderService;
    private BreadcrumbsService $breadcrumbs;

    private $request;

    public function __construct(CartService $cart, CustomOrderService $customOrderService, BreadcrumbsService $breadcrumbs, RequestStack $requestStack)
    {
        $this->cart = $cart;
        $this->customOrderService = $customOrderService;
        $this->request = $requestStack->getCurrentRequest();
        $this->breadcrumbs = $breadcrumbs->addRoute('home');
    }

    /**
     * @Route("/objednavka-na-miru/{token}", name="order_custom")
     */
    public function orderCustom($token = null): Response
    {
        if ($this->customOrderService->loadCustomOrder($token) === null)
        {
            throw $this->createNotFoundException('Objednávka nenalezena.');
        }

        $this->breadcrumbs->addRoute('order_custom', ['token' => $token]);

        return $this->render('order/custom_overview.html.twig', [
            'order' => $this->customOrderService->getOrder(),
            'custom_order_service' => $this->customOrderService,
        ]);
    }

    /**
     * @Route("/kosik", name="order_cart")
     */
    public function cart(): Response
    {
        $order = $this->cart->getOrder();
        $form = $this->createForm(CartFormType::class, $order);
        $formView = $form->createView();

        $this->breadcrumbs->addRoute('order_cart');

        return $this->render('order/cart.html.twig', [
            'cartForm' => $formView,
        ]);
    }

    /**
     * @Route("/objednavka/doprava-a-platba/{token}", name="order_methods")
     */
    public function orderMethods(JsonResponseService $jsonResponse, $token = null): Response
    {
        $targetOrder = $this->cart->getOrder();

        if($token !== null)
        {
            if ($this->customOrderService->loadCustomOrder($token) === null)
            {
                throw $this->createNotFoundException('Objednávka nenalezena.');
            }

            $targetOrder = $this->customOrderService->getOrder();
            $this->breadcrumbs->addRoute('order_custom', ['token' => $token]);
        }
        else
        {
            $this->breadcrumbs->addRoute('order_cart');
        }

        $form = $this->createForm(OrderMethodsFormType::class, $targetOrder);
        // tlačítko se přidává v šabloně
        $form->handleRequest($this->request);

        if ($form->isSubmitted() && $form->isValid())
        {
            $this->getDoctrine()->getManager()->persist($targetOrder);
            $this->getDoctrine()->getManager()->flush();

            if(!$this->request->isXmlHttpRequest())
            {
                $this->addFlash('success', 'saved');
                return $this->redirectToRoute('order_methods');
            }
        }

        $this->breadcrumbs->addRoute('order_methods');

        if($this->request->isXmlHttpRequest())
        {
            return $jsonResponse
                ->setResponseHtml($this->renderView('fragments/forms_unique/_form_order_methods.html.twig', [
                    'order' => $targetOrder,
                    'token'=> $token,
                    'orderMethodsForm' => $form->createView()
                ]))
                ->createJsonResponse()
            ;
        }
        else
        {
            return $this->render('order/methods.html.twig', [
                'order' => $targetOrder,
                'token'=> $token,
                'orderMethodsForm' => $form->createView(),
            ]);
        }
    }
}