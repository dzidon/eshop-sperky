<?php

namespace App\Controller\Admin;

use App\Entity\Order;
use App\Form\CustomOrderFormType;
use App\Form\HiddenTrueFormType;
use App\Form\OrderCancelFormType;
use App\Form\OrderEditFormType;
use App\Form\OrderSearchFormType;
use App\Service\BreadcrumbsService;
use App\Service\OrderCompletionService;
use App\Service\PaginatorService;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;

/**
 * @Route("/admin")
 *
 * @IsGranted("IS_AUTHENTICATED_FULLY")
 */
class OrderController extends AbstractController
{
    private LoggerInterface $logger;
    private BreadcrumbsService $breadcrumbs;
    private $request;

    public function __construct(LoggerInterface $logger, BreadcrumbsService $breadcrumbs, RequestStack $requestStack)
    {
        $this->logger = $logger;
        $this->breadcrumbs = $breadcrumbs;
        $this->request = $requestStack->getCurrentRequest();

        $this->breadcrumbs
            ->addRoute('home')
            ->addRoute('admin_permission_overview', [], MainController::ADMIN_TITLE)
            ->addRoute('admin_orders')
        ;
    }

    /**
     * @Route("/objednavky", name="admin_orders")
     *
     * @IsGranted("admin_orders")
     */
    public function orders(FormFactoryInterface $formFactory, PaginatorService $paginatorService): Response
    {
        $form = $formFactory->createNamed('', OrderSearchFormType::class, null, ['sort_choices' => Order::getSortData()]);
        // button je přidáván v šabloně, aby se nezobrazoval v odkazu
        $form->handleRequest($this->request);

        if ($form->isSubmitted() && $form->isValid())
        {
            $queryForPagination = $this->getDoctrine()->getRepository(Order::class)->getQueryForAdminSearchAndPagination($form->get('searchPhrase')->getData(), $form->get('sortBy')->getData(), $form->get('lifecycle')->getData());
        }
        else
        {
            $queryForPagination = $this->getDoctrine()->getRepository(Order::class)->getQueryForAdminSearchAndPagination();
        }

        $orders = $paginatorService
            ->initialize($queryForPagination, 20)
            ->getCurrentPageObjects();

        if($paginatorService->isCurrentPageOutOfBounds())
        {
            throw new NotFoundHttpException('Na této stránce nebyly nalezeny žádné objednávky.');
        }

        return $this->render('admin/orders/admin_orders.html.twig', [
            'searchForm' => $form->createView(),
            'orders' => $orders,
            'pagination' => $paginatorService->createViewData(),
        ]);
    }

    /**
     * @Route("/objednavka-na-miru/{id}", name="admin_order_custom_edit", requirements={"id"="\d+"})
     *
     * @IsGranted("order_edit_custom")
     */
    public function orderCustom(int $id = null): Response
    {
        $user = $this->getUser();

        if ($id !== null) // zadal id do url, snazi se editovat existujici
        {
            $order = $this->getDoctrine()->getRepository(Order::class)->findOneForAdminCustomEdit($id);
            if($order === null) // nenaslo to zadnou objednavku na miru
            {
                throw new NotFoundHttpException('Objednávka nenalezena.');
            }

            $this->breadcrumbs->addRoute('admin_order_custom_edit', ['id' => $order->getId()],'', 'edit');
        }
        else // nezadal id do url, vytvari novou skupinu
        {
            $order = new Order();
            $order->setCreatedManually(true);
            $this->breadcrumbs->addRoute('admin_order_custom_edit', ['id' => null],'', 'new');
        }

        $form = $this->createForm(CustomOrderFormType::class, $order);
        $form->add('submit', SubmitType::class, ['label' => 'Uložit']);
        $form->handleRequest($this->request);

        if ($form->isSubmitted() && $form->isValid())
        {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($order);
            $entityManager->flush();

            $this->addFlash('success', 'Objednávka na míru uložena!');
            $this->logger->info(sprintf("Admin %s (ID: %s) has saved a custom order ID %s.", $user->getUserIdentifier(), $user->getId(), $order->getId()));

            return $this->redirectToRoute('admin_order_custom_edit', ['id' => $order->getId()]);
        }

        return $this->render('admin/orders/admin_order_custom_edit.html.twig', [
            'orderCustomForm' => $form->createView(),
            'orderInstance' => $order,
        ]);
    }

    /**
     * @Route("/objednavka-na-miru/{id}/smazat", name="admin_order_custom_delete", requirements={"id"="\d+"})
     *
     * @IsGranted("order_delete_custom")
     */
    public function orderCustomDelete($id): Response
    {
        $user = $this->getUser();

        $order = $this->getDoctrine()->getRepository(Order::class)->findOneForAdminCustomEdit($id);
        if ($order === null) // nenaslo to zadnou objednavku
        {
            throw new NotFoundHttpException('Objednávka nenalezena.');
        }

        $form = $this->createForm(HiddenTrueFormType::class, null, ['csrf_token_id' => 'form_order_custom_delete']);
        $form->add('submit', SubmitType::class, [
            'label' => 'Smazat',
            'attr' => ['class' => 'btn-large red left'],
        ]);
        $form->handleRequest($this->request);

        if ($form->isSubmitted() && $form->isValid())
        {
            $this->logger->info(sprintf("Admin %s (ID: %s) has deleted a custom order ID %s.", $user->getUserIdentifier(), $user->getId(), $order->getId()));

            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($order);
            $entityManager->flush();

            $this->addFlash('success', 'Objednávka na míru smazána!');
            return $this->redirectToRoute('admin_orders');
        }

        $this->breadcrumbs->addRoute('admin_order_custom_delete', ['id' => $order->getId()]);

        return $this->render('admin/orders/admin_order_custom_delete.html.twig', [
            'orderCustomDeleteForm' => $form->createView(),
            'orderCustomInstance' => $order,
        ]);
    }

    /**
     * @Route("/objednavka/{id}", name="admin_order_overview", requirements={"id"="\d+"})
     *
     * @IsGranted("order_edit")
     */
    public function order(int $id = null): Response
    {
        $user = $this->getUser();

        /** @var Order|null $order */
        $order = $this->getDoctrine()->getRepository(Order::class)->findOneForAdminEdit($id);
        if ($order === null) // nenaslo to zadnou objednavku
        {
            throw new NotFoundHttpException('Objednávka nenalezena.');
        }

        $order->calculateTotals();

        /*
         * Formulář pro změnu stavu
         */
        $formLifecycleChapterView = null;
        if (!$order->isCancelled())
        {
            $formLifecycleChapter = $this->createForm(OrderEditFormType::class, $order);
            $formLifecycleChapter->add('submit', SubmitType::class, [
                'label' => 'Nastavit',
                'attr' => ['class' => 'btn-large blue left'],
            ]);
            $formLifecycleChapter->handleRequest($this->request);
            $formLifecycleChapterView = $formLifecycleChapter->createView();

            if ($formLifecycleChapter->isSubmitted() && $formLifecycleChapter->isValid())
            {
                $entityManager = $this->getDoctrine()->getManager();
                $entityManager->persist($order);
                $entityManager->flush();

                $this->logger->info(sprintf("Admin %s (ID: %s) has changed the state of order ID %s to %s.", $user->getUserIdentifier(), $user->getId(), $order->getId(), $order->getLifecycleChapter()));
                $this->addFlash('success', 'Stav změněn!');

                return $this->redirectToRoute('admin_order_overview', ['id' => $order->getId()]);
            }
        }

        $this->breadcrumbs->addRoute('order_overview');

        return $this->render('admin/orders/admin_order_overview.html.twig', [
            'order' => $order,
            'formLifecycleChapter' => $formLifecycleChapterView,
        ]);
    }

    /**
     * @Route("/objednavka/{id}/zrusit", name="admin_order_cancel", requirements={"id"="\d+"})
     *
     * @IsGranted("order_cancel")
     */
    public function orderCancel(OrderCompletionService $orderCompletionService, $id): Response
    {
        $user = $this->getUser();

        /** @var Order|null $order */
        $order = $this->getDoctrine()->getRepository(Order::class)->findOneForAdminCancellation($id);
        if ($order === null || $order->isCancelled()) // nenaslo to zadnou objednavku nebo uz je cancelled
        {
            throw new NotFoundHttpException('Objednávka nenalezena.');
        }

        $form = $this->createForm(OrderCancelFormType::class, $order);
        $form->add('submit', SubmitType::class, [
            'label' => 'Zrušit',
            'attr' => ['class' => 'btn-large red left'],
        ]);
        $form->handleRequest($this->request);

        if ($form->isSubmitted() && $form->isValid())
        {
            $orderCompletionService
                ->setOrder($order)
                ->cancelOrder($forceInventoryReplenish = false)
                ->sendConfirmationEmail()
            ;

            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($order);
            $entityManager->flush();

            $this->logger->info(sprintf("Admin %s (ID: %s) has cancelled an order ID %s.", $user->getUserIdentifier(), $user->getId(), $order->getId()));
            $this->addFlash('success', 'Objednávka zrušena!');

            return $this->redirectToRoute('admin_orders');
        }

        $this->breadcrumbs->addRoute('admin_order_cancel', ['id' => $order->getId()]);

        return $this->render('admin/orders/admin_order_cancel.html.twig', [
            'orderCancelForm' => $form->createView(),
            'orderInstance' => $order,
        ]);
    }
}