<?php

namespace App\Service;

use App\Entity\Product;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class SeoService
{
    private UrlGeneratorInterface $router;
    private EntityManagerInterface $entityManager;

    public function __construct(UrlGeneratorInterface $router, EntityManagerInterface $entityManager)
    {
        $this->router = $router;
        $this->entityManager = $entityManager;
    }

    public function getSitemapUrls(): array
    {
        $urls = [];

        $urls[] = $this->router->generate('home', [], UrlGeneratorInterface::ABSOLUTE_URL);
        $urls[] = $this->router->generate('contact', [], UrlGeneratorInterface::ABSOLUTE_URL);
        $urls[] = $this->router->generate('forgot_password_request', [], UrlGeneratorInterface::ABSOLUTE_URL);
        $urls[] = $this->router->generate('register', [], UrlGeneratorInterface::ABSOLUTE_URL);
        $urls[] = $this->router->generate('login', [], UrlGeneratorInterface::ABSOLUTE_URL);
        $urls[] = $this->router->generate('products', [], UrlGeneratorInterface::ABSOLUTE_URL);
        $urls[] = $this->router->generate('order_custom_new', [], UrlGeneratorInterface::ABSOLUTE_URL);

        $products = $this->entityManager->getRepository(Product::class)->findAllVisible();
        /** @var Product $product */
        foreach ($products as $product)
        {
            $urls[] = $this->router->generate('product', ['slug' => $product->getSlug()], UrlGeneratorInterface::ABSOLUTE_URL);
        }

        return $urls;
    }
}