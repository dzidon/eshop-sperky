<?php

namespace App\Controller\Admin;

use App\Entity\ProductOption;
use App\Form\HiddenTrueFormType;
use App\Form\ProductOptionFormType;
use App\Form\ProductOptionParametersFormType;
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
class ProductOptionController extends AbstractController
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
     * @Route("/produktove-volby", name="admin_product_options")
     *
     * @IsGranted("admin_product_options")
     */
    public function productOptions(FormFactoryInterface $formFactory, PaginatorService $paginatorService): Response
    {
        $form = $formFactory->createNamed('', SearchTextAndSortFormType::class, null, ['sort_choices' => ProductOption::getSortData()]);
        //button je přidáván v šabloně, aby se nezobrazoval v odkazu
        $form->handleRequest($this->request);

        if ($form->isSubmitted() && $form->isValid())
        {
            $queryForPagination = $this->getDoctrine()->getRepository(ProductOption::class)->getQueryForSearchAndPagination($form->get('vyraz')->getData(), $form->get('razeni')->getData());
        }
        else
        {
            $queryForPagination = $this->getDoctrine()->getRepository(ProductOption::class)->getQueryForSearchAndPagination();
        }

        $page = (int) $this->request->query->get(PaginatorService::QUERY_PARAMETER_PAGE_NAME, '1');
        $options = $paginatorService
            ->initialize($queryForPagination, 3, $page)
            ->getCurrentPageObjects();

        if($paginatorService->isCurrentPageOutOfBounds())
        {
            throw new NotFoundHttpException('Na této stránce nebyly nalezeny žádné produktové volby.');
        }

        return $this->render('admin/product_options/admin_product_options.html.twig', [
            'searchForm' => $form->createView(),
            'options' => $options,
            'breadcrumbs' => $this->breadcrumbs->setPageTitleByRoute('admin_product_options'),
            'pagination' => $paginatorService->createViewData(),
        ]);
    }

    /**
     * @Route("/produktova-volba/{id}", name="admin_product_option_edit", requirements={"id"="\d+"})
     *
     * @IsGranted("product_option_edit")
     */
    public function productOption($id = null): Response
    {
        $user = $this->getUser();

        if($id !== null) //zadal id do url, snazi se editovat existujici
        {
            $option = $this->getDoctrine()->getRepository(ProductOption::class)->findOneBy(['id' => $id]);
            if($option === null) //nenaslo to zadnou volbu
            {
                throw new NotFoundHttpException('Produktová volba nenalezena.');
            }
            $this->breadcrumbs->setPageTitleByRoute('admin_product_option_edit', 'edit');
        }
        else //nezadal id do url, vytvari novou volbu
        {
            $option = new ProductOption();
            $this->breadcrumbs->setPageTitleByRoute('admin_product_option_edit', 'new');
        }

        $form = $this->createForm(ProductOptionFormType::class, $option);
        $form->add('submit', SubmitType::class, ['label' => 'Uložit a pokračovat']);
        $form->handleRequest($this->request);

        if ($form->isSubmitted() && $form->isValid())
        {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($option);
            $entityManager->flush();

            $this->addFlash('success', 'Produktová volba uložena! Nyní ji nakonfigurujte.');
            $this->logger->info(sprintf("Admin %s (ID: %s) has saved a product option %s (ID: %s).", $user->getUserIdentifier(), $user->getId(), $option->getName(), $option->getId()));

            return $this->redirectToRoute('admin_product_option_configure', ['id' => $option->getId()]);
        }

        return $this->render('admin/product_options/admin_product_option_edit.html.twig', [
            'productOptionForm' => $form->createView(),
            'productOptionInstance' => $option,
            'breadcrumbs' => $this->breadcrumbs,
        ]);
    }

    /**
     * @Route("/produktova-volba/{id}/smazat", name="admin_product_option_delete", requirements={"id"="\d+"})
     *
     * @IsGranted("product_option_delete")
     */
    public function productOptionDelete($id): Response
    {
        $user = $this->getUser();

        $option = $this->getDoctrine()->getRepository(ProductOption::class)->findOneBy(['id' => $id]);
        if($option === null) //nenaslo to zadnou volbu
        {
            throw new NotFoundHttpException('Produktová volba nenalezena.');
        }

        $form = $this->createForm(HiddenTrueFormType::class, null, ['csrf_token_id' => 'form_product_option_delete']);
        $form->add('submit', SubmitType::class, [
            'label' => 'Smazat',
            'attr' => ['class' => 'btn-large red left'],
        ]);
        $form->handleRequest($this->request);

        if ($form->isSubmitted() && $form->isValid())
        {
            $this->logger->info(sprintf("Admin %s (ID: %s) has deleted a product option %s (ID: %s).", $user->getUserIdentifier(), $user->getId(), $option->getName(), $option->getId()));

            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($option);
            $entityManager->flush();

            $this->addFlash('success', 'Produktová volba smazána!');
            return $this->redirectToRoute('admin_product_options');
        }

        return $this->render('admin/product_options/admin_product_option_delete.html.twig', [
            'productOptionDeleteForm' => $form->createView(),
            'productOptionInstance' => $option,
            'breadcrumbs' => $this->breadcrumbs->setPageTitleByRoute('admin_product_option_delete'),
        ]);
    }

    /**
     * @Route("/produktova-volba/{id}/konfigurovat", name="admin_product_option_configure", requirements={"id"="\d+"})
     *
     * @IsGranted("product_option_edit")
     */
    public function productOptionConfigure($id): Response
    {
        $user = $this->getUser();

        $option = $this->getDoctrine()->getRepository(ProductOption::class)->findOneBy(['id' => $id]);
        if($option === null) //nenaslo to zadnou volbu
        {
            throw new NotFoundHttpException('Produktová volba nenalezena.');
        }

        $form = $this->createForm(ProductOptionParametersFormType::class, $option);
        $form->add('submit', SubmitType::class, ['label' => 'Uložit']);
        $form->handleRequest($this->request);

        if ($form->isSubmitted() && $form->isValid())
        {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($option);
            $entityManager->flush();

            $this->addFlash('success', 'Produktová volba uložena a nakonfigurována!');
            $this->logger->info(sprintf("Admin %s (ID: %s) has configured a product option %s (ID: %s).", $user->getUserIdentifier(), $user->getId(), $option->getName(), $option->getId()));

            return $this->redirectToRoute('admin_product_options');
        }

        return $this->render('admin/product_options/admin_product_option_configure.html.twig', [
            'productOptionConfigureForm' => $form->createView(),
            'productOptionInstance' => $option,
            'breadcrumbs' => $this->breadcrumbs->setPageTitleByRoute('admin_product_option_configure'),
        ]);
    }
}