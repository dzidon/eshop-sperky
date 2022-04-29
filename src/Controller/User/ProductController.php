<?php

namespace App\Controller\User;

use App\Entity\Detached\CartInsert;
use App\Entity\Detached\Search\SearchProduct;
use App\Entity\Product;
use App\Entity\ProductSection;
use App\Form\CartInsertFormType;
use App\Form\ProductCatalogFilterFormType;
use App\Service\BreadcrumbsService;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
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
    public function products(FormFactoryInterface $formFactory, string $slug = null): Response
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
        }

        $priceData = $this->getDoctrine()->getRepository(Product::class)->getMinAndMaxPrice($section);
        $filterData = new SearchProduct(Product::getSortDataForCatalog(), 'Hledejte podle názvu.');
        $filterData->setPriceMin($priceData['priceMin']);
        $filterData->setPriceMax($priceData['priceMax']);
        $filterData->setSection($section);

        $form = $formFactory->createNamed('', ProductCatalogFilterFormType::class, $filterData);
        // button je přidáván v šabloně, aby se nezobrazoval v odkazu
        $form->handleRequest($this->request);

        $pagination = $this->getDoctrine()->getRepository(Product::class)->getSearchPagination($inAdmin = false, $filterData);
        $pagination->addAttributesToPathParameters(['slug']);
        if($pagination->isCurrentPageOutOfBounds())
        {
            throw new NotFoundHttpException('Na této stránce nebyly nalezeny žádné produkty.');
        }

        if ($this->request->isXmlHttpRequest())
        {
            $response = $this->render('fragments/forms_unique/_form_product_catalog.html.twig', [
                'filterForm' => $form->createView(),
                'products' => $pagination->getCurrentPageObjects(),
                'pagination' => $pagination->createView(),
            ]);

            $response->headers->add([
                'Cache-Control' => 'no-store, must-revalidate, max-age=0',
                'Pragma' => 'no-cache',
                'Expires' => 'Sat, 26 Jul 1997 05:00:00 GMT',
            ]);

            return $response;
        }
        else
        {
            if($section === null)
            {
                $this->breadcrumbs->addRoute('products', [], 'Všechny produkty');
            }
            else
            {
                $this->breadcrumbs->addRoute('products', [], $section->getName());
            }

            return $this->render('products/catalog.html.twig', [
                'filterForm' => $form->createView(),
                'products' => $pagination->getCurrentPageObjects(),
                'pagination' => $pagination->createView(),
            ]);
        }
    }

    /**
     * @Route("/produkt/{slug}", name="product")
     */
    public function product(string $slug): Response
    {
        /** @var Product $product */
        $product = $this->getDoctrine()->getRepository(Product::class)->findOneAndFetchEverything(['slug' => $slug], $visibleOnly = true);
        if($product === null)
        {
            throw new NotFoundHttpException('Produkt nenalezen.');
        }

        $cartInsertRequest = new CartInsert();
        $cartInsertRequest->setProduct($product);
        $form = $this->createForm(CartInsertFormType::class, $cartInsertRequest);

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

        $this->breadcrumbs
            ->addRoute('products', ['slug' => $sectionData['slug']], $sectionData['title'])
            ->addRoute('product', ['slug' => $product->getSlug()], $product->getName());

        return $this->render('products/product.html.twig', [
            'productInstance' => $product,
            'relatedProducts' => $relatedProducts,
            'cartInsertForm' => $form->createView(),
        ]);
    }
}