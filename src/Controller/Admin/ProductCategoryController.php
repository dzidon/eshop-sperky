<?php

namespace App\Controller\Admin;

use App\Entity\ProductCategoryGroup;
use App\Form\HiddenTrueFormType;
use App\Form\ProductCategoryGroupFormType;
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
class ProductCategoryController extends AbstractController
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
     * @Route("/skupiny-produktovych-kategorii", name="admin_product_categories")
     *
     * @IsGranted("admin_product_categories")
     */
    public function productCategoryGroups(FormFactoryInterface $formFactory, PaginatorService $paginatorService): Response
    {
        $form = $formFactory->createNamed('', SearchTextAndSortFormType::class, null, ['sort_choices' => ProductCategoryGroup::getSortData()]);
        //button je přidáván v šabloně, aby se nezobrazoval v odkazu
        $form->handleRequest($this->request);

        if ($form->isSubmitted() && $form->isValid())
        {
            $queryForPagination = $this->getDoctrine()->getRepository(ProductCategoryGroup::class)->getQueryForSearchAndPagination($form->get('vyraz')->getData(), $form->get('razeni')->getData());
        }
        else
        {
            $queryForPagination = $this->getDoctrine()->getRepository(ProductCategoryGroup::class)->getQueryForSearchAndPagination();
        }

        $page = (int) $this->request->query->get(PaginatorService::QUERY_PARAMETER_PAGE_NAME, '1');
        $categoryGroups = $paginatorService
            ->initialize($queryForPagination, 1, $page)
            ->getCurrentPageObjects();

        if($paginatorService->isCurrentPageOutOfBounds())
        {
            throw new NotFoundHttpException('Na této stránce nebyly nalezeny žádné skupiny produktových kategorií.');
        }

        return $this->render('admin/product_categories/admin_product_categories.html.twig', [
            'searchForm' => $form->createView(),
            'categoryGroups' => $categoryGroups,
            'breadcrumbs' => $this->breadcrumbs->setPageTitleByRoute('admin_product_categories'),
            'pagination' => $paginatorService->createViewData(),
        ]);
    }

    /**
     * @Route("/skupina-produktovych-kategorii/{id}", name="admin_product_category_edit", requirements={"id"="\d+"})
     *
     * @IsGranted("product_category_edit")
     */
    public function productCategoryGroup($id = null): Response
    {
        $user = $this->getUser();

        if($id !== null) //zadal id do url, snazi se editovat existujici
        {
            $categoryGroup = $this->getDoctrine()->getRepository(ProductCategoryGroup::class)->findOneByIdAndFetchCategories($id);
            if($categoryGroup === null) //nenaslo to zadnou skupinu
            {
                throw new NotFoundHttpException('Skupina produktových sekcí nenalezena.');
            }
            $this->breadcrumbs->setPageTitleByRoute('admin_product_category_edit', 'edit');
        }
        else //nezadal id do url, vytvari novou skupinu
        {
            $categoryGroup = new ProductCategoryGroup();
            $this->breadcrumbs->setPageTitleByRoute('admin_product_category_edit', 'new');
        }

        $form = $this->createForm(ProductCategoryGroupFormType::class, $categoryGroup);
        $form->add('submit', SubmitType::class, ['label' => 'Uložit']);
        $form->handleRequest($this->request);

        if ($form->isSubmitted() && $form->isValid())
        {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($categoryGroup);
            $entityManager->flush();

            $this->addFlash('success', 'Skupina produktových kategorií uložena!');
            $this->logger->info(sprintf("Admin %s (ID: %s) has saved a product category group %s (ID: %s).", $user->getUserIdentifier(), $user->getId(), $categoryGroup->getName(), $categoryGroup->getId()));

            return $this->redirectToRoute('admin_product_categories');
        }

        return $this->render('admin/product_categories/admin_product_category_edit.html.twig', [
            'productCategoryGroupForm' => $form->createView(),
            'productCategoryGroupInstance' => $categoryGroup,
            'breadcrumbs' => $this->breadcrumbs,
        ]);
    }

    /**
     * @Route("/skupina-produktovych-kategorii/{id}/smazat", name="admin_product_category_delete", requirements={"id"="\d+"})
     *
     * @IsGranted("product_category_delete")
     */
    public function productCategoryGroupDelete($id): Response
    {
        $user = $this->getUser();

        $categoryGroup = $this->getDoctrine()->getRepository(ProductCategoryGroup::class)->findOneBy(['id' => $id]);
        if($categoryGroup === null) //nenaslo to zadnou sekci
        {
            throw new NotFoundHttpException('Skupina produktových kategorií nenalezena.');
        }

        $form = $this->createForm(HiddenTrueFormType::class, null, ['csrf_token_id' => 'form_product_category_group_delete']);
        $form->add('submit', SubmitType::class, [
            'label' => 'Smazat',
            'attr' => ['class' => 'btn-large red left'],
        ]);
        $form->handleRequest($this->request);

        if ($form->isSubmitted() && $form->isValid())
        {
            $this->logger->info(sprintf("Admin %s (ID: %s) has deleted a product category group %s (ID: %s).", $user->getUserIdentifier(), $user->getId(), $categoryGroup->getName(), $categoryGroup->getId()));

            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($categoryGroup);
            $entityManager->flush();

            $this->addFlash('success', 'Skupina produktových kategorií smazána!');
            return $this->redirectToRoute('admin_product_categories');
        }

        return $this->render('admin/product_categories/admin_product_category_delete.html.twig', [
            'productCategoryGroupDeleteForm' => $form->createView(),
            'productCategoryGroupInstance' => $categoryGroup,
            'breadcrumbs' => $this->breadcrumbs->setPageTitleByRoute('admin_product_category_delete'),
        ]);
    }
}