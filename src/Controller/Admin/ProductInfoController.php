<?php

namespace App\Controller\Admin;

use App\Entity\ProductInformationGroup;
use App\Form\HiddenTrueFormType;
use App\Form\ProductInformationGroupFormType;
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
class ProductInfoController extends AbstractController
{
    private LoggerInterface $logger;
    private BreadcrumbsService $breadcrumbs;
    private $request;

    public function __construct(LoggerInterface $logger, BreadcrumbsService $breadcrumbs, RequestStack $requestStack)
    {
        $this->logger = $logger;
        $this->breadcrumbs = $breadcrumbs;
        $this->request = $requestStack->getCurrentRequest();

        $this->breadcrumbs->addRoute('home')->addRoute('admin_permission_overview', [], MainController::ADMIN_TITLE);
    }

    /**
     * @Route("/skupiny-produktovych-informaci", name="admin_product_info")
     *
     * @IsGranted("admin_product_info")
     */
    public function productInfoGroups(FormFactoryInterface $formFactory, PaginatorService $paginatorService): Response
    {
        $form = $formFactory->createNamed('', SearchTextAndSortFormType::class, null, ['sort_choices' => ProductInformationGroup::getSortData()]);
        //button je přidáván v šabloně, aby se nezobrazoval v odkazu
        $form->handleRequest($this->request);

        if ($form->isSubmitted() && $form->isValid())
        {
            $queryForPagination = $this->getDoctrine()->getRepository(ProductInformationGroup::class)->getQueryForSearchAndPagination($form->get('vyraz')->getData(), $form->get('razeni')->getData());
        }
        else
        {
            $queryForPagination = $this->getDoctrine()->getRepository(ProductInformationGroup::class)->getQueryForSearchAndPagination();
        }

        $page = (int) $this->request->query->get(PaginatorService::QUERY_PARAMETER_PAGE_NAME, '1');
        $infoGroups = $paginatorService
            ->initialize($queryForPagination, 1, $page)
            ->getCurrentPageObjects();

        if($paginatorService->isCurrentPageOutOfBounds())
        {
            throw new NotFoundHttpException('Na této stránce nebyly nalezeny žádné skupiny produktových informací.');
        }

        return $this->render('admin/product_info/admin_product_info.html.twig', [
            'searchForm' => $form->createView(),
            'infoGroups' => $infoGroups,
            'breadcrumbs' => $this->breadcrumbs->setPageTitleByRoute('admin_product_info'),
            'pagination' => $paginatorService->createViewData(),
        ]);
    }

    /**
     * @Route("/skupina-produktovych-informaci/{id}", name="admin_product_info_edit", requirements={"id"="\d+"})
     *
     * @IsGranted("product_info_edit")
     */
    public function productInfoGroup($id = null): Response
    {
        $user = $this->getUser();

        if($id !== null) //zadal id do url, snazi se editovat existujici
        {
            $infoGroup = $this->getDoctrine()->getRepository(ProductInformationGroup::class)->findOneBy(['id' => $id]);
            if($infoGroup === null) //nenaslo to zadnou skupinu
            {
                throw new NotFoundHttpException('Skupina produktových informaci nenalezena.');
            }
            $this->breadcrumbs->setPageTitleByRoute('admin_product_info_edit', 'edit');
        }
        else //nezadal id do url, vytvari novou skupinu
        {
            $infoGroup = new ProductInformationGroup();
            $this->breadcrumbs->setPageTitleByRoute('admin_product_info_edit', 'new');
        }

        $form = $this->createForm(ProductInformationGroupFormType::class, $infoGroup);
        $form->add('submit', SubmitType::class, ['label' => 'Uložit']);
        $form->handleRequest($this->request);

        if ($form->isSubmitted() && $form->isValid())
        {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($infoGroup);
            $entityManager->flush();

            $this->addFlash('success', 'Skupina produktových informací uložena!');
            $this->logger->info(sprintf("Admin %s (ID: %s) has saved a product information group %s (ID: %s).", $user->getUserIdentifier(), $user->getId(), $infoGroup->getName(), $infoGroup->getId()));

            return $this->redirectToRoute('admin_product_info');
        }

        return $this->render('admin/product_info/admin_product_info_edit.html.twig', [
            'productInfoGroupForm' => $form->createView(),
            'productInfoGroupInstance' => $infoGroup,
            'breadcrumbs' => $this->breadcrumbs,
        ]);
    }

    /**
     * @Route("/skupina-produktovych-informaci/{id}/smazat", name="admin_product_info_delete", requirements={"id"="\d+"})
     *
     * @IsGranted("product_info_delete")
     */
    public function productInfoGroupDelete($id): Response
    {
        $user = $this->getUser();

        $infoGroup = $this->getDoctrine()->getRepository(ProductInformationGroup::class)->findOneBy(['id' => $id]);
        if($infoGroup === null) //nenaslo to zadnou skupinu
        {
            throw new NotFoundHttpException('Skupina produktových informací nenalezena.');
        }

        $form = $this->createForm(HiddenTrueFormType::class, null, ['csrf_token_id' => 'form_product_info_group_delete']);
        $form->add('submit', SubmitType::class, [
            'label' => 'Smazat',
            'attr' => ['class' => 'btn-large red left'],
        ]);
        $form->handleRequest($this->request);

        if ($form->isSubmitted() && $form->isValid())
        {
            $this->logger->info(sprintf("Admin %s (ID: %s) has deleted a product information group %s (ID: %s).", $user->getUserIdentifier(), $user->getId(), $infoGroup->getName(), $infoGroup->getId()));

            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($infoGroup);
            $entityManager->flush();

            $this->addFlash('success', 'Skupina produktových informací smazána!');
            return $this->redirectToRoute('admin_product_info');
        }

        return $this->render('admin/product_info/admin_product_info_delete.html.twig', [
            'productInfoGroupDeleteForm' => $form->createView(),
            'productInfoGroupInstance' => $infoGroup,
            'breadcrumbs' => $this->breadcrumbs->setPageTitleByRoute('admin_product_info_delete'),
        ]);
    }
}