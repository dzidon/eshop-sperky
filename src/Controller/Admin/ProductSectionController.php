<?php

namespace App\Controller\Admin;

use App\Entity\ProductSection;
use App\Form\HiddenTrueFormType;
use App\Form\ProductSectionFormType;
use App\Form\SearchTextAndSortFormType;
use App\Service\BreadcrumbsService;
use App\Service\EntityUpdatingService;
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
class ProductSectionController extends AbstractController
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
     * @Route("/produktove-sekce", name="admin_product_sections")
     *
     * @IsGranted("admin_product_sections")
     */
    public function productSections(FormFactoryInterface $formFactory, PaginatorService $paginatorService): Response
    {
        $form = $formFactory->createNamed('', SearchTextAndSortFormType::class, null, ['sort_choices' => ProductSection::getSortData()]);
        //button je přidáván v šabloně, aby se nezobrazoval v odkazu
        $form->handleRequest($this->request);

        if ($form->isSubmitted() && $form->isValid())
        {
            $queryForPagination = $this->getDoctrine()->getRepository(ProductSection::class)->getQueryForSearchAndPagination($form->get('vyraz')->getData(), $form->get('razeni')->getData());
        }
        else
        {
            $queryForPagination = $this->getDoctrine()->getRepository(ProductSection::class)->getQueryForSearchAndPagination();
        }

        $page = (int) $this->request->query->get(PaginatorService::QUERY_PARAMETER_PAGE_NAME, '1');
        $sections = $paginatorService
            ->initialize($queryForPagination, 1, $page)
            ->getCurrentPageObjects();

        if($paginatorService->isCurrentPageOutOfBounds())
        {
            throw new NotFoundHttpException('Na této stránce nebyly nalezeny žádné sekce.');
        }

        return $this->render('admin/product_sections/admin_product_sections.html.twig', [
            'searchForm' => $form->createView(),
            'sections' => $sections,
            'breadcrumbs' => $this->breadcrumbs->setPageTitleByRoute('admin_product_sections'),
            'pagination' => $paginatorService->createViewData(),
        ]);
    }

    /**
     * @Route("/produktova-sekce/{id}", name="admin_product_section_edit", requirements={"id"="\d+"})
     *
     * @IsGranted("product_section_edit")
     */
    public function productSection(EntityUpdatingService $entityUpdater, $id = null): Response
    {
        $user = $this->getUser();

        if($id !== null) //zadal id do url, snazi se editovat existujici
        {
            $section = $this->getDoctrine()->getRepository(ProductSection::class)->findOneBy(['id' => $id]);
            if($section === null) //nenaslo to zadnou sekci
            {
                throw new NotFoundHttpException('Produktová sekce nenalezena.');
            }
            $this->breadcrumbs->setPageTitleByRoute('admin_product_section_edit', 'edit');
        }
        else //nezadal id do url, vytvari novou sekci
        {
            $section = new ProductSection();
            $this->breadcrumbs->setPageTitleByRoute('admin_product_section_edit', 'new');
        }

        $form = $this->createForm(ProductSectionFormType::class, $section);
        $form->add('submit', SubmitType::class, ['label' => 'Uložit']);
        $form->handleRequest($this->request);

        if ($form->isSubmitted() && $form->isValid())
        {
            $entityUpdater->setMainInstance($section)
                ->mainInstancePersistOrSetUpdated();
            $this->getDoctrine()->getManager()->flush();

            $this->addFlash('success', 'Produktová sekce uložena!');
            $this->logger->info(sprintf("Admin %s (ID: %s) has saved a product section %s (ID: %s).", $user->getUserIdentifier(), $user->getId(), $section->getName(), $section->getId()));

            return $this->redirectToRoute('admin_product_sections');
        }

        return $this->render('admin/product_sections/admin_product_section_edit.html.twig', [
            'productSectionForm' => $form->createView(),
            'productSectionInstance' => $section,
            'breadcrumbs' => $this->breadcrumbs,
        ]);
    }

    /**
     * @Route("/produktova-sekce/{id}/smazat", name="admin_product_section_delete", requirements={"id"="\d+"})
     *
     * @IsGranted("product_section_delete")
     */
    public function productSectionDelete($id): Response
    {
        $user = $this->getUser();

        $section = $this->getDoctrine()->getRepository(ProductSection::class)->findOneBy(['id' => $id]);
        if($section === null) //nenaslo to zadnou sekci
        {
            throw new NotFoundHttpException('Produktová sekce nenalezena.');
        }

        $form = $this->createForm(HiddenTrueFormType::class, null, ['csrf_token_id' => 'form_product_section_delete']);
        $form->add('submit', SubmitType::class, [
            'label' => 'Smazat',
            'attr' => ['class' => 'btn-large red left'],
        ]);
        $form->handleRequest($this->request);

        if ($form->isSubmitted() && $form->isValid())
        {
            $this->logger->info(sprintf("Admin %s (ID: %s) has deleted a product section %s (ID: %s).", $user->getUserIdentifier(), $user->getId(), $section->getName(), $section->getId()));

            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($section);
            $entityManager->flush();

            $this->addFlash('success', 'Produktová sekce smazána!');
            return $this->redirectToRoute('admin_product_sections');
        }

        return $this->render('admin/product_sections/admin_product_section_delete.html.twig', [
            'productSectionDeleteForm' => $form->createView(),
            'productSectionInstance' => $section,
            'breadcrumbs' => $this->breadcrumbs->setPageTitleByRoute('admin_product_section_delete'),
        ]);
    }
}