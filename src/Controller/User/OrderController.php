<?php

namespace App\Controller\User;

use App\Form\CartFormType;
use App\Service\BreadcrumbsService;
use App\Service\CartService;
use App\Service\CustomOrderService;
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
        $this->customOrderService->loadCustomOrder($token);
        if ($this->customOrderService->getOrder() === null)
        {
            throw $this->createNotFoundException('Objednávka nenalezena.');
        }

        $this->breadcrumbs->addRoute('order_custom');

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
}