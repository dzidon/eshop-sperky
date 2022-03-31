<?php

namespace App\Controller\Admin;

use App\Entity\PaymentMethod;
use App\Form\PaymentMethodFormType;
use App\Form\SearchTextAndSortFormType;
use App\Service\BreadcrumbsService;
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
class PaymentMethodController extends AbstractController
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
            ->addRoute('admin_payment_methods');
    }

    /**
     * @Route("/platebni-metody", name="admin_payment_methods")
     *
     * @IsGranted("admin_payment_methods")
     */
    public function paymentMethods(FormFactoryInterface $formFactory, PaginatorService $paginatorService): Response
    {
        $form = $formFactory->createNamed('', SearchTextAndSortFormType::class, null, ['sort_choices' => PaymentMethod::getSortData()]);
        //button je přidáván v šabloně, aby se nezobrazoval v odkazu
        $form->handleRequest($this->request);

        if ($form->isSubmitted() && $form->isValid())
        {
            $queryForPagination = $this->getDoctrine()->getRepository(PaymentMethod::class)->getQueryForSearchAndPagination($form->get('searchPhrase')->getData(), $form->get('sortBy')->getData());
        }
        else
        {
            $queryForPagination = $this->getDoctrine()->getRepository(PaymentMethod::class)->getQueryForSearchAndPagination();
        }

        $paymentMethods = $paginatorService
            ->initialize($queryForPagination, 1)
            ->getCurrentPageObjects();

        if($paginatorService->isCurrentPageOutOfBounds())
        {
            throw new NotFoundHttpException('Na této stránce nebyly nalezeny žádné platební metody.');
        }

        return $this->render('admin/payment_methods/admin_payment_methods.html.twig', [
            'searchForm' => $form->createView(),
            'paymentMethods' => $paymentMethods,
            'pagination' => $paginatorService->createViewData(),
        ]);
    }

    /**
     * @Route("/platebni-metoda/{id}", name="admin_payment_method_edit", requirements={"id"="\d+"})
     *
     * @IsGranted("payment_method_edit")
     */
    public function paymentMethod($id): Response
    {
        $user = $this->getUser();

        $paymentMethod = $this->getDoctrine()->getRepository(PaymentMethod::class)->findOneBy(['id' => $id]);
        if($paymentMethod === null)
        {
            throw new NotFoundHttpException('Platební metoda nenalezena.');
        }

        $this->breadcrumbs->addRoute('admin_payment_method_edit', ['id' => $paymentMethod->getId()]);

        $form = $this->createForm(PaymentMethodFormType::class, $paymentMethod);
        $form->add('submit', SubmitType::class, ['label' => 'Uložit']);
        $form->handleRequest($this->request);

        if ($form->isSubmitted() && $form->isValid())
        {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($paymentMethod);
            $entityManager->flush();

            $this->addFlash('success', 'Platební metoda uložena!');
            $this->logger->info(sprintf("Admin %s (ID: %s) has saved a payment method %s (ID: %s).", $user->getUserIdentifier(), $user->getId(), $paymentMethod->getType(), $paymentMethod->getId()));

            return $this->redirectToRoute('admin_payment_methods');
        }

        return $this->render('admin/payment_methods/admin_payment_method_edit.html.twig', [
            'paymentMethodForm' => $form->createView(),
            'paymentMethodInstance' => $paymentMethod,
        ]);
    }
}