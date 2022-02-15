<?php

namespace App\Controller\Admin;

use App\Entity\Product;
use App\Form\HiddenTrueFormType;
use App\Form\ProductFormType;
use App\Form\SearchTextAndSortFormType;
use App\Service\BreadcrumbsService;
use App\Service\EntityCollectionService;
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
class ProductController extends AbstractController
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
     * @Route("/produkty", name="admin_products")
     *
     * @IsGranted("admin_products")
     */
    public function products(FormFactoryInterface $formFactory, PaginatorService $paginatorService): Response
    {
        $form = $formFactory->createNamed('', SearchTextAndSortFormType::class, null, ['sort_choices' => Product::getSortData()['admin']]);
        //button je přidáván v šabloně, aby se nezobrazoval v odkazu
        $form->handleRequest($this->request);

        if ($form->isSubmitted() && $form->isValid())
        {
            $queryForPagination = $this->getDoctrine()->getRepository(Product::class)->getQueryForSearchAndPagination($inAdmin = true, $form->get('vyraz')->getData(), $form->get('razeni')->getData());
        }
        else
        {
            $queryForPagination = $this->getDoctrine()->getRepository(Product::class)->getQueryForSearchAndPagination($inAdmin = true);
        }

        $page = (int) $this->request->query->get(PaginatorService::QUERY_PARAMETER_PAGE_NAME, '1');
        $products = $paginatorService
            ->initialize($queryForPagination, 1, $page)
            ->getCurrentPageObjects();

        if($paginatorService->isCurrentPageOutOfBounds())
        {
            throw new NotFoundHttpException('Na této stránce nebyly nalezeny žádné produkty.');
        }

        return $this->render('admin/products/admin_product_management.html.twig', [
            'searchForm' => $form->createView(),
            'products' => $products,
            'breadcrumbs' => $this->breadcrumbs->setPageTitleByRoute('admin_products'),
            'pagination' => $paginatorService->createViewData(),
        ]);
    }

    /**
     * @Route("/produkt/{id}", name="admin_product_edit", requirements={"id"="\d+"})
     *
     * @IsGranted("product_edit")
     */
    public function product(EntityUpdatingService $entityUpdater, EntityCollectionService $entityCollectionManager, $id = null): Response
    {
        $user = $this->getUser();

        if($id !== null) //zadal id do url, snazi se editovat existujici
        {
            $product = $this->getDoctrine()->getRepository(Product::class)->findOneByIdAndFetchEverything($id);
            if($product === null) //nenaslo to zadny produkt
            {
                throw new NotFoundHttpException('Produkt nenalezen.');
            }
            $this->breadcrumbs->setPageTitleByRoute('admin_product_edit', 'edit');
        }
        else //nezadal id do url, vytvari novy produkt
        {
            $product = new Product();
            $this->breadcrumbs->setPageTitleByRoute('admin_product_edit', 'new');
        }

        $entityCollectionManager->loadCollections([
            ['type' => 'old', 'name' => 'info', 'collection' => $product->getInfo()]
        ]);

        $form = $this->createForm(ProductFormType::class, $product);
        $form->add('submit', SubmitType::class, ['label' => 'Uložit']);
        $form->handleRequest($this->request);

        if ($form->isSubmitted() && $form->isValid())
        {
            $entityUpdater
                ->setMainInstance($product)
                ->setCollectionGetters(['getInfo'])
                ->mainInstancePersistOrSetUpdated()
                ->collectionItemsSetUpdated();

            $entityCollectionManager
                ->loadCollections([
                    ['type' => 'new', 'name' => 'info', 'collection' => $product->getInfo()]
                ])
                ->removeElementsMissingFromNewCollection();

            $this->getDoctrine()->getManager()->flush();

            $this->addFlash('success', 'Produkt uložen!');
            $this->logger->info(sprintf("Admin %s (ID: %s) has saved a product %s (ID: %s).", $user->getUserIdentifier(), $user->getId(), $product->getName(), $product->getId()));

            return $this->redirectToRoute('admin_products');
        }

        return $this->render('admin/products/admin_product_management_specific.html.twig', [
            'productForm' => $form->createView(),
            'productInstance' => $product,
            'breadcrumbs' => $this->breadcrumbs,
        ]);
    }

    /**
     * @Route("/produkt/{id}/smazat", name="admin_product_delete", requirements={"id"="\d+"})
     *
     * @IsGranted("product_delete")
     */
    public function productDelete($id): Response
    {
        $user = $this->getUser();

        $product = $this->getDoctrine()->getRepository(Product::class)->findOneBy(['id' => $id]);
        if($product === null) //nenaslo to zadny produkt
        {
            throw new NotFoundHttpException('Produkt nenalezen.');
        }

        $form = $this->createForm(HiddenTrueFormType::class, null, ['csrf_token_id' => 'form_product_delete']);
        $form->add('submit', SubmitType::class, [
            'label' => 'Smazat',
            'attr' => ['class' => 'btn-large red left'],
        ]);
        $form->handleRequest($this->request);

        if ($form->isSubmitted() && $form->isValid())
        {
            $this->logger->info(sprintf("Admin %s (ID: %s) has deleted a product %s (ID: %s).", $user->getUserIdentifier(), $user->getId(), $product->getName(), $product->getId()));

            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($product);
            $entityManager->flush();

            $this->addFlash('success', 'Produkt smazán!');
            return $this->redirectToRoute('admin_products');
        }

        return $this->render('admin/products/admin_product_management_delete.html.twig', [
            'productDeleteForm' => $form->createView(),
            'productInstance' => $product,
            'breadcrumbs' => $this->breadcrumbs->setPageTitleByRoute('admin_product_delete'),
        ]);
    }
}