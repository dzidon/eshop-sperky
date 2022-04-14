<?php

namespace App\Controller\User;

use App\Entity\Address;
use App\Entity\Order;
use App\Form\CartFormType;
use App\Form\OrderAddressesFormType;
use App\Form\OrderMethodsFormType;
use App\Service\BreadcrumbsService;
use App\Service\CartService;
use App\Service\CustomOrderService;
use App\Service\JsonResponseService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Uid\Uuid;
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

        $this->breadcrumbs->addRoute('order_cart');

        return $this->render('order/cart.html.twig', [
            'cartForm' => $form->createView(),
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
            if (($targetOrder = $this->customOrderService->loadCustomOrder($token)) === null)
            {
                throw $this->createNotFoundException('Objednávka nenalezena.');
            }

            $this->breadcrumbs
                ->addRoute('order_custom', ['token' => $token])
                ->addRoute('order_methods', ['token' => $token]);
        }
        else
        {
            $this->breadcrumbs
                ->addRoute('order_cart')
                ->addRoute('order_methods');
        }

        $form = $this->createForm(OrderMethodsFormType::class, $targetOrder, [
            'action' => $this->generateUrl('order_methods', ['token' => $token]),
        ]);
        // tlačítko se přidává v šabloně
        $form->handleRequest($this->request);

        if ($form->isSubmitted() && $form->isValid())
        {
            $this->getDoctrine()->getManager()->persist($targetOrder);
            $this->getDoctrine()->getManager()->flush();

            if(!$this->request->isXmlHttpRequest())
            {
                return $this->redirectToRoute('order_addresses', ['token' => $token]);
            }
        }

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

    /**
     * @Route("/objednavka/dodaci-udaje/{token}", name="order_addresses")
     */
    public function orderAddresses($token = null): Response
    {
        $targetOrder = $this->cart->getOrder();
        $synchronizerHasWarnings = $this->cart->getSynchronizer()->hasWarnings();

        if ($token !== null)
        {
            if (($targetOrder = $this->customOrderService->loadCustomOrder($token)) === null)
            {
                throw $this->createNotFoundException('Objednávka nenalezena.');
            }

            $synchronizerHasWarnings = $this->customOrderService->getSynchronizer()->hasWarnings();
            $this->breadcrumbs
                ->addRoute('order_custom', ['token' => $token])
                ->addRoute('order_methods', ['token' => $token])
                ->addRoute('order_addresses', ['token' => $token]);
        }
        else
        {
            $this->breadcrumbs
                ->addRoute('order_cart')
                ->addRoute('order_methods')
                ->addRoute('order_addresses');
        }

        $form = $this->createForm(OrderAddressesFormType::class, $targetOrder);
        // tlačítko se přidává v šabloně
        $form->handleRequest($this->request);

        if ($form->isSubmitted() && $form->isValid() && !$synchronizerHasWarnings)
        {
            $targetOrder->finish();
            $this->getDoctrine()->getManager()->persist($targetOrder);
            $this->getDoctrine()->getManager()->flush();
            $this->addFlash('success', 'Objednávka vytvořena!');

            $response = $this->redirectToRoute('home');
            if (!$targetOrder->isCreatedManually())
            {
                $response->headers->clearCookie(CartService::COOKIE_NAME);
            }
            return $response;
        }

        return $this->render('order/addresses.html.twig', [
            'order' => $targetOrder,
            'token'=> $token,
            'orderAddressesForm' => $form->createView(),
        ]);
    }

    /**
     * @Route("/objednavka/nacist-adresu", name="order_address_load", methods={"POST"})
     */
    public function loadAddress(JsonResponseService $jsonResponse): Response
    {
        $user = $this->getUser();
        if ($user === null)
        {
            $jsonResponse->addResponseError('Nejste přihlášený.');
            return $jsonResponse->createJsonResponse();
        }

        $addressId = $this->request->request->get('addressId');
        if ($addressId === null)
        {
            $jsonResponse->addResponseError('Musíte vybrat platnou adresu.');
            return $jsonResponse->createJsonResponse();
        }

        /** @var Address $address */
        $address = $this->getDoctrine()->getManager()->getRepository(Address::class)->findOneBy(['id' => $addressId, 'user' => $user]);
        if ($address === null)
        {
            $jsonResponse->addResponseError('Vybraná adresa nebyla nalezena.');
            return $jsonResponse->createJsonResponse();
        }

        $addressData = [
            'nameFirst' => $address->getNameFirst(),
            'nameLast' => $address->getNameLast(),
            'country' => $address->getCountry(),
            'street' => $address->getStreet(),
            'additionalInfo' => $address->getAdditionalInfo(),
            'town' => $address->getTown(),
            'zip' => $address->getZip(),
            'company' => $address->getCompany(),
            'ic' => $address->getIc(),
            'dic' => $address->getDic(),
        ];
        $jsonResponse->setResponseData('addressData', $addressData);
        return $jsonResponse->createJsonResponse();
    }

    /**
     * @Route("/objednavka/prehled/{token}", name="order_overview")
     */
    public function orderOverview($token = null): Response
    {
        if ($token !== null)
        {
            $this->request->getSession()->set('OrderPublicToken', $token);
            return $this->redirectToRoute('order_overview');
        }

        $token = $this->request->getSession()->get('OrderPublicToken');
        if ($token === null || !UUid::isValid($token))
        {
            throw new NotFoundHttpException('Neplatný token.');
        }

        $uuid = Uuid::fromString($token);
        /** @var Order|null $order */
        $order = $this->getDoctrine()->getRepository(Order::class)->findOneAndFetchCartOccurences($uuid);
        if ($order === null || $order->getLifecycleChapter() < Order::LIFECYCLE_AWAITING_PAYMENT)
        {
            throw new NotFoundHttpException('Objednávka nenalezena.');
        }

        $order->calculateTotals();
        $this->breadcrumbs->addRoute('order_overview', ['token' => $token]);

        return $this->render('order/overview.html.twig', [
            'order' => $order,
        ]);
    }
}