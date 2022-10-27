<?php

namespace App\Controller;

use App\Service\Seo;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class SeoController extends AbstractController
{
    private Seo $seo;

    public function __construct(Seo $seo)
    {
        $this->seo = $seo;
    }

    /**
     * @Route("/sitemap.xml", name="sitemap", defaults={"_format"="xml"})
     */
    public function sitemap(): Response
    {
        return $this->render('seo/sitemap.xml.twig', [
            'urls' => $this->seo->getSitemapUrls(),
        ]);
    }

    /**
     * @Route("/robots.txt", name="robots", defaults={"_format"="txt"})
     */
    public function robots(): Response
    {
        return $this->render('seo/robots.txt.twig');
    }
}