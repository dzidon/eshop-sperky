<?php

namespace App\Controller\Admin;

use App\Entity\DeliveryMethod;
use App\Entity\Detached\Search\Atomic\Dropdown;
use App\Entity\Detached\Search\Atomic\Phrase;
use App\Entity\Detached\Search\Atomic\Sort;
use App\Entity\Detached\Search\Composition\PhraseSort;
use App\Entity\Detached\Search\Composition\PhraseSortDropdown;
use App\Entity\Order;
use App\Exception\PacketaException;
use App\Form\FormType\Admin\CustomOrderFormType;
use App\Form\FormType\Search\Composition\PhraseSortDropdownFormType;
use App\Form\FormType\User\HiddenTrueFormType;
use App\Form\FormType\Admin\OrderCancelFormType;
use App\Form\FormType\Admin\OrderEditFormType;
use App\Form\FormType\Admin\OrderPacketaFormType;
use App\Service\Breadcrumbs;
use App\Service\OrphanRemoval;
use App\Facade\OrderFacade;
use App\Service\Packeta;
use Psr\Log\LoggerInterface;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;

/**
 * @Route("/admin")
 *
 * @IsGranted("IS_AUTHENTICATED_FULLY")
 */
class OrderController extends AbstractAdminController
{
    private LoggerInterface $logger;

    public function __construct(LoggerInterface $logger, Breadcrumbs $breadcrumbs)
    {
        parent::__construct($breadcrumbs);

        $this->breadcrumbs->addRoute('admin_orders');
        $this->logger = $logger;
    }

    /**
     * @Route("/objednavky", name="admin_orders")
     *
     * @IsGranted("admin_orders")
     */
    public function orders(FormFactoryInterface $formFactory, Request $request): Response
    {
        $phraseSort = new PhraseSort(new Phrase('Hledejte podle ID.'), new Sort(Order::getSortData()));
        $dropdown = new Dropdown(array_flip(Order::LIFECYCLE_CHAPTERS), 'Typ', '-- všechny --');
        $searchData = new PhraseSortDropdown($phraseSort, $dropdown);

        $form = $formFactory->createNamed('', PhraseSortDropdownFormType::class, $searchData);
        // button je přidáván v šabloně, aby se nezobrazoval v odkazu
        $form->handleRequest($request);

        $pagination = $this->getDoctrine()->getRepository(Order::class)->getAdminSearchPagination($searchData);
        if($pagination->isCurrentPageOutOfBounds())
        {
            throw new NotFoundHttpException('Na této stránce nebyly nalezeny žádné objednávky.');
        }

        return $this->render('admin/orders/admin_orders.html.twig', [
            'searchForm' => $form->createView(),
            'orders' => $pagination->getCurrentPageObjects(),
            'pagination' => $pagination->createView(),
        ]);
    }

    /**
     * @Route("/objednavka-na-miru/{id}", name="admin_order_custom_edit", requirements={"id"="\d+"})
     *
     * @IsGranted("order_edit_custom")
     */
    public function orderCustom(OrphanRemoval $orphanRemoval, Request $request, int $id = null): Response
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

        $collectionMessenger = $orphanRemoval->createEntityCollectionsMessengerForOrphanRemoval($order);
        $form = $this->createForm(CustomOrderFormType::class, $order);
        $form->add('submit', SubmitType::class, ['label' => 'Uložit']);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid())
        {
            $orphanRemoval->removeOrphans($collectionMessenger);
            $order->calculatePricesWithVatForCartOccurences();
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
    public function orderCustomDelete(Request $request, $id): Response
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
        $form->handleRequest($request);

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
    public function order(Packeta $packeta, Request $request, int $id = null): Response
    {
        $user = $this->getUser();

        /** @var Order|null $order */
        $order = $this->getDoctrine()->getRepository(Order::class)->findOneForAdminEdit($id);
        if ($order === null) // nenaslo to zadnou objednavku
        {
            throw new NotFoundHttpException('Objednávka nenalezena.');
        }

        $formLifecycleChapterView = null;
        $formPacketaView = null;
        $packetaMessage = null;

        if (!$order->isCancelled())
        {
            /*
             * Formulář pro změnu stavu
             */
            $formLifecycleChapter = $this->createForm(OrderEditFormType::class, $order);
            $formLifecycleChapter->add('submit', SubmitType::class, [
                'label' => 'Nastavit',
                'attr' => ['class' => 'btn-medium blue left'],
            ]);
            $formLifecycleChapter->handleRequest($request);
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

            /*
             * Formulář pro odeslání do Zásilkovny
             */
            if ($order->getLifecycleChapter() > Order::LIFECYCLE_AWAITING_PAYMENT && $order->getDeliveryMethod() !== null && $order->getDeliveryMethod()->getType() === DeliveryMethod::TYPE_PACKETA_CZ)
            {
                if ($packeta->packetExists($order))
                {
                    $packetaMessage = 'Zásilka je připravena v systému Zásilkovny.';
                }
                else
                {
                    $formPacketa = $this->createForm(OrderPacketaFormType::class, $order);
                    $formPacketa->add('submit', SubmitType::class, [
                        'label' => 'Vytvořit',
                        'attr' => ['class' => 'btn-medium blue left'],
                    ]);
                    $formPacketa->handleRequest($request);
                    $formPacketaView = $formPacketa->createView();

                    if ($formPacketa->isSubmitted() && $formPacketa->isValid())
                    {
                        try
                        {
                            $packeta->createPacket($order);
                            $this->addFlash('success', 'Zásilka vytvořena!');
                            return $this->redirectToRoute('admin_order_overview', ['id' => $order->getId()]);
                        }
                        catch (PacketaException $exception)
                        {
                            foreach ($exception->getErrors() as $error)
                            {
                                $this->addFlash('failure', $error);
                            }
                        }
                    }
                }
            }
        }

        $this->breadcrumbs->addRoute('order_overview');

        return $this->render('admin/orders/admin_order_overview.html.twig', [
            'order' => $order,
            'formLifecycleChapter' => $formLifecycleChapterView,
            'formPacketa' => $formPacketaView,
            'packetaMessage' => $packetaMessage,
        ]);
    }

    /**
     * @Route("/objednavka/{id}/zrusit", name="admin_order_cancel", requirements={"id"="\d+"})
     *
     * @IsGranted("order_cancel")
     */
    public function orderCancel(OrderFacade $orderFacade, Request $request, $id): Response
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
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid())
        {
            $orderFacade->cancelOrder($order, false, true);
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