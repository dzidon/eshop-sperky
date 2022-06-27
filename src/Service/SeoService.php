<?php

namespace App\Service;

use App\Entity\Product;
use App\Entity\ProductSection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Třída řešící SEO.
 *
 * @package App\Service
 */
class SeoService
{
    private UrlGeneratorInterface $router;
    private EntityManagerInterface $entityManager;

    public function __construct(UrlGeneratorInterface $router, EntityManagerInterface $entityManager)
    {
        $this->router = $router;
        $this->entityManager = $entityManager;
    }

    /**
     * Vrátí odkazy pro sitemap.xml
     *
     * @return array
     */
    public function getSitemapUrls(): array
    {
        $urls = [];

        $urls[] = $this->router->generate('home', [], UrlGeneratorInterface::ABSOLUTE_URL);
        $urls[] = $this->router->generate('contact', [], UrlGeneratorInterface::ABSOLUTE_URL);
        $urls[] = $this->router->generate('forgot_password_request', [], UrlGeneratorInterface::ABSOLUTE_URL);
        $urls[] = $this->router->generate('register', [], UrlGeneratorInterface::ABSOLUTE_URL);
        $urls[] = $this->router->generate('login', [], UrlGeneratorInterface::ABSOLUTE_URL);
        $urls[] = $this->router->generate('order_custom_new', [], UrlGeneratorInterface::ABSOLUTE_URL);
        $urls[] = $this->router->generate('products', [], UrlGeneratorInterface::ABSOLUTE_URL);

        // sekce produktů
        $sections = $this->entityManager->getRepository(ProductSection::class)->findAllVisible();
        /** @var ProductSection $product */
        foreach ($sections as $section)
        {
            $urls[] = $this->router->generate('products', ['slug' => $section->getSlug()], UrlGeneratorInterface::ABSOLUTE_URL);
        }

        // produkty
        $products = $this->entityManager->getRepository(Product::class)->findAllVisible();
        /** @var Product $product */
        foreach ($products as $product)
        {
            $urls[] = $this->router->generate('product', ['slug' => $product->getSlug()], UrlGeneratorInterface::ABSOLUTE_URL);
        }

        return $urls;
    }
}