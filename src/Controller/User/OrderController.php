<?php

namespace App\Controller\User;

use App\Form\CartFormType;
use App\Service\BreadcrumbsService;
use App\Service\CartService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class OrderController extends AbstractController
{
    private CartService $cart;
    private BreadcrumbsService $breadcrumbs;
    private $request;

    public function __construct(CartService $cart, BreadcrumbsService $breadcrumbs, RequestStack $requestStack)
    {
        $this->cart = $cart;
        $this->request = $requestStack->getCurrentRequest();
        $this->breadcrumbs = $breadcrumbs->addRoute('home');
    }

    /**
     * @Route("/kosik", name="order_cart")
     */
    public function cart(): Response
    {
        $this->breadcrumbs->addRoute('order_cart');

        $formView = null;
        $order = $this->cart->getOrder();
        if (!$order->getCartOccurences()->isEmpty())
        {
            $form = $this->createForm(CartFormType::class, $order);
            $form->add('submit', SubmitType::class, ['label' => 'Aktualizovat košík']);

            if (!$this->cart->hasSynchronizationWarnings())
            {
                $form->handleRequest($this->request);

                if ($form->isSubmitted() && $form->isValid())
                {

                }
            }

            $formView = $form->createView();
        }

        return $this->render('order/cart.html.twig', [
            'cartForm' => $formView,
        ]);
    }
}