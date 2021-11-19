<?php

namespace App\Controller;

use App\Service\BreadcrumbsService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class MainController extends AbstractController
{
    /**
     * @Route("/", name="home")
     */
    public function index(BreadcrumbsService $breadcrumbs): Response
    {
        $breadcrumbs->addRoute('home');

        return $this->render('main/index.html.twig', [
            'breadcrumbs' => $breadcrumbs,
        ]);
    }
}
