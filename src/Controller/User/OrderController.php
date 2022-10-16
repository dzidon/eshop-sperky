<?php

namespace App\Controller\User;

use App\Entity\Address;
use App\Entity\Order;
use App\Exception\PaymentException;
use App\Facade\PaymentFacade;
use App\Form\FormType\User\OrderAddressesFormType;
use App\Form\FormType\User\OrderMethodsFormType;
use App\Form\FormType\User\CartFormType;
use App\Response\Json;
use App\Service\BreadcrumbsService;
use App\Service\CartService;
use App\Facade\OrderFacade;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Routing\Annotation\Route;

class OrderController extends AbstractController
{
    private CartService $cart;
    private OrderFacade $orderFacade;
    private BreadcrumbsService $breadcrumbs;

    public function __construct(CartService $cart, OrderFacade $orderFacade, BreadcrumbsService $breadcrumbs)
    {
        $this->cart = $cart;
        $this->orderFacade = $orderFacade;
        $this->breadcrumbs = $breadcrumbs->addRoute('home');
    }

    /**
     * @Route("/objednavka-na-miru/{token}", name="order_custom")
     */
    public function orderCustom($token = null): Response
    {
        if (($order = $this->orderFacade->loadCustomOrder($token)) === null)
        {
            throw $this->createNotFoundException('Objednávka nenalezena.');
        }

        $this->breadcrumbs->addRoute('order_custom', ['token' => $token]);

        return $this->render('order/custom_overview.html.twig', [
            'order' => $order,
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
            'order' => $order,
        ]);
    }

    /**
     * @Route("/objednavka/doprava-a-platba/{token}", name="order_methods")
     */
    public function orderMethods(Request $request, $token = null): Response
    {
        $targetOrder = $this->cart->getOrder();

        if($token !== null)
        {
            if (($targetOrder = $this->orderFacade->loadCustomOrder($token)) === null)
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
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid())
        {
            $this->getDoctrine()->getManager()->persist($targetOrder);
            $this->getDoctrine()->getManager()->flush();

            if(!$request->isXmlHttpRequest())
            {
                return $this->redirectToRoute('order_addresses', ['token' => $token]);
            }
        }

        if($request->isXmlHttpRequest())
        {
            $jsonResponse = new Json();
            return $jsonResponse
                ->setResponseHtml($this->renderView('fragments/forms_unique/_form_order_methods.html.twig', [
                    'order' => $targetOrder,
                    'token'=> $token,
                    'orderMethodsForm' => $form->createView()
                ]))
                ->create()
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
    public function orderAddresses(OrderFacade $orderFacade, PaymentFacade $paymentFacade, Request $request, LoggerInterface $logger, $token = null): Response
    {
        $targetOrder = $this->cart->getOrder();

        if ($token !== null)
        {
            if (($targetOrder = $this->orderFacade->loadCustomOrder($token)) === null)
            {
                throw $this->createNotFoundException('Objednávka nenalezena.');
            }

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
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid() && !$targetOrder->hasSynchronizationWarnings())
        {
            try
            {
                $payment = null /*$this->paymentFacade->createPayment($order, false)*/; // ještě není napojená platební brána
                $response = $orderFacade->finishOrder($targetOrder, $payment, true);
                $logger->info(sprintf('Order ID %d has been finished. Current lifecycle chapter: %d.', $targetOrder->getId(), $targetOrder->getLifecycleChapter()));
                return $response;
            }
            catch (PaymentException $exception)
            {
                $this->addFlash('failure', $exception->getMessage());
            }
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
    public function loadAddress(Request $request): Response
    {
        $jsonResponse = new Json();
        $user = $this->getUser();
        if ($user === null)
        {
            $jsonResponse->addResponseError('Nejste přihlášený.');
            return $jsonResponse->create();
        }

        $addressId = $request->request->get('addressId');
        if ($addressId === null)
        {
            $jsonResponse->addResponseError('Musíte vybrat platnou adresu.');
            return $jsonResponse->create();
        }

        /** @var Address $address */
        $address = $this->getDoctrine()->getManager()->getRepository(Address::class)->findOneBy(['id' => $addressId, 'user' => $user]);
        if ($address === null)
        {
            $jsonResponse->addResponseError('Vybraná adresa nebyla nalezena.');
            return $jsonResponse->create();
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
        return $jsonResponse->create();
    }

    /**
     * @Route("/objednavka/prehled/{token}", name="order_overview")
     */
    public function orderPublicOverview(Request $request, $token = null): Response
    {
        if ($token !== null)
        {
            $request->getSession()->set('OrderPublicToken', $token);
            return $this->redirectToRoute('order_overview');
        }

        $token = $request->getSession()->get('OrderPublicToken');
        if ($token === null || !UUid::isValid($token))
        {
            throw new NotFoundHttpException('Neplatný token.');
        }

        $uuid = Uuid::fromString($token);
        /** @var Order|null $order */
        $order = $this->getDoctrine()->getRepository(Order::class)->findOneForPublicOverview($uuid);
        if ($order === null)
        {
            throw new NotFoundHttpException('Objednávka nenalezena.');
        }

        $this->breadcrumbs->addRoute('order_overview');

        return $this->render('order/overview.html.twig', [
            'order' => $order,
        ]);
    }
}