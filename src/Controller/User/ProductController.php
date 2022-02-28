<?php

namespace App\Controller\User;

use App\Entity\Product;
use App\Entity\ProductSection;
use App\Service\BreadcrumbsService;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Entity;

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
    public function products(string $slug = null): Response
    {
        $section = null;
        if($slug)
        {
            $section = $this->getDoctrine()->getRepository(ProductSection::class)->findOneBy(['slug' => $slug]);
            if($section === null)
            {
                throw new NotFoundHttpException('Sekce nenalezena.');
            }
        }

        $this->breadcrumbs->addRoute('products');

        return new Response("todo");
    }

    /**
     * @Route("/produkt/{slug}", name="product")
     */
    public function product(string $slug): Response
    {
        /** @var Product $product */
        $product = $this->getDoctrine()->getRepository(Product::class)->findOneByIdAndFetchEverything(['slug' => $slug]);
        if($product === null || !$product->isVisible())
        {
            throw new NotFoundHttpException('Produkt nenalezen.');
        }

        $section = $product->getSection();
        $sectionData = [
            'slug' => ($section !== null ? $section->getSlug() : null),
            'title' => ($section !== null ? $section->getName() : 'Všechny produkty'),
        ];

        return $this->render('products/product.html.twig', [
            'productInstance' => $product,
            'breadcrumbs' => $this->breadcrumbs
                ->addRoute('products', ['slug' => $sectionData['slug']], $sectionData['title'])
                ->addRoute('product', ['slug' => $product->getSlug()], $product->getName()),
        ]);
    }
}