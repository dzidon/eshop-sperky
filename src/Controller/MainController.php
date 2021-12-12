<?php

namespace App\Controller;

use App\Entity\Review;
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
        $latestReviews = $this->getDoctrine()->getRepository(Review::class)->findLatest(4);
        $totalAndAverage = $this->getDoctrine()->getRepository(Review::class)->getTotalAndAverage();

        return $this->render('main/index.html.twig', [
            'agregateReviewData' => $totalAndAverage,
            'latestReviews' => $latestReviews,
            'breadcrumbs' => $breadcrumbs->addRoute('home'),
        ]);
    }
}
