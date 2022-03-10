<?php

namespace App\Controller\User;

use App\Entity\Detached\ProductCatalogFilter;
use App\Entity\Product;
use App\Entity\ProductSection;
use App\Form\CartInsertFormType;
use App\Form\ProductCatalogFilterFormType;
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

        $this->breadcrumbs->addRoute('home');
    }

    /**
     * @Route("/produkty/{slug}", name="products")
     */
    public function products(string $slug = null, FormFactoryInterface $formFactory, PaginatorService $paginatorService): Response
    {
        $section = null;
        if($slug)
        {
            /** @var ProductSection $section */
            $section = $this->getDoctrine()->getRepository(ProductSection::class)->findOneBy(['slug' => $slug]);
            if($section === null)
            {
                throw new NotFoundHttpException('Sekce nenalezena.');
            }

            $this->breadcrumbs->addRoute('products', [], $section->getName());
        }
        else
        {
            $this->breadcrumbs->addRoute('products', [], 'Všechny produkty');
        }

        $filterData = new ProductCatalogFilter();
        $filterData->setSection($section);

        $form = $formFactory->createNamed('', ProductCatalogFilterFormType::class, $filterData);
        // button je přidáván v šabloně, aby se nezobrazoval v odkazu
        $form->handleRequest($this->request);

        if ($form->isSubmitted() && $form->isValid())
        {
            $queryForPagination = $this->getDoctrine()->getRepository(Product::class)->getQueryForSearchAndPagination($inAdmin = false, $filterData->getSection(), $filterData->getSearchPhrase(), $filterData->getSortBy(), $filterData->getPriceMin(), $filterData->getPriceMax(), $filterData->getCategoriesGrouped());
        }
        else
        {
            $queryForPagination = $this->getDoctrine()->getRepository(Product::class)->getQueryForSearchAndPagination($inAdmin = false, $filterData->getSection());
        }

        $products = $paginatorService
            ->initialize($queryForPagination, 2)
            ->addAttributesToPathParameters(['slug'])
            ->getCurrentPageObjects();

        if($paginatorService->isCurrentPageOutOfBounds())
        {
            throw new NotFoundHttpException('Na této stránce nebyly nalezeny žádné produkty.');
        }

        return $this->render('products/catalog.html.twig', [
            'filterForm' => $form->createView(),
            'products' => $products,
            'breadcrumbs' => $this->breadcrumbs,
            'pagination' => $paginatorService->createViewData(),
        ]);
    }

    /**
     * @Route("/produkt/{slug}", name="product")
     */
    public function product(string $slug): Response
    {
        /** @var Product $product */
        $product = $this->getDoctrine()->getRepository(Product::class)->findOneAndFetchEverything(['slug' => $slug]);
        if($product === null || !$product->isVisible())
        {
            throw new NotFoundHttpException('Produkt nenalezen.');
        }

        $form = $this->createForm(CartInsertFormType::class);
        $form->add('submit', SubmitType::class, ['label' => 'Do košíku']);

        $relatedProducts = null;
        if($product->getSection() !== null)
        {
            $relatedProducts = $this->getDoctrine()->getRepository(Product::class)->findRelated($product, 4);
        }

        $section = $product->getSection();
        $sectionData = [
            'slug'  => ($section !== null ? $section->getSlug() : null),
            'title' => ($section !== null ? $section->getName() : 'Všechny produkty'),
        ];

        return $this->render('products/product.html.twig', [
            'productInstance' => $product,
            'relatedProducts' => $relatedProducts,
            'cartInsertForm' => $form->createView(),
            'breadcrumbs' => $this->breadcrumbs
                ->addRoute('products', ['slug' => $sectionData['slug']], $sectionData['title'])
                ->addRoute('product', ['slug' => $product->getSlug()], $product->getName()),
        ]);
    }
}