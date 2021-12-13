<?php

namespace App\Controller;

use App\Entity\Review;
use App\Form\HiddenTrueFormType;
use App\Service\BreadcrumbsService;
use App\Service\PaginatorService;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;

/**
 * @Route("/recenze")
 */
class ReviewController extends AbstractController
{
    private LoggerInterface $logger;
    private BreadcrumbsService $breadcrumbs;
    private $request;

    public function __construct(LoggerInterface $logger, BreadcrumbsService $breadcrumbs, RequestStack $requestStack)
    {
        $this->logger = $logger;
        $this->breadcrumbs = $breadcrumbs;
        $this->request = $requestStack->getCurrentRequest();

        $this->breadcrumbs->addRoute('home')->addRoute('reviews');
    }

    /**
     * @Route("", name="reviews")
     */
    public function reviews(PaginatorService $paginatorService): Response
    {
        $page = (int) $this->request->query->get('page', '1');
        $queryForPagination = $this->getDoctrine()->getRepository(Review::class)->getQueryForPagination();
        $reviews = $paginatorService
            ->initialize($queryForPagination, 5, $page)
            ->getCurrentPageObjects();

        if($paginatorService->isPageOutOfBounds($paginatorService->getCurrentPage()))
        {
            throw new NotFoundHttpException('Na této stránce nebyly nalezeny žádné recenze.');
        }

        $totalAndAverage = $this->getDoctrine()->getRepository(Review::class)->getTotalAndAverage();

        return $this->render('reviews/reviews_overview.html.twig', [
            'reviews' => $reviews,
            'agregateReviewData' => $totalAndAverage,
            'breadcrumbs' => $this->breadcrumbs,
            'pagination' => $paginatorService->createViewData(),
        ]);
    }

    /**
     * @Route("/{id}/smazat", name="review_delete", requirements={"id"="\d+"})
     *
     * @IsGranted("IS_AUTHENTICATED_REMEMBERED")
     */
    public function reviewDelete($id): Response
    {
        $user = $this->getUser();
        $review = $this->getDoctrine()->getRepository(Review::class)->findOneBy([
            'id' => $id,
        ]);

        if($review === null || $review->getUser() !== $user) //nenaslo to zadnou adresu, nebo to nejakou adresu naslo, ale ta neni uzivatele
        {
            throw new NotFoundHttpException('Recenze nenalezena.');
        }

        $form = $this->createForm(HiddenTrueFormType::class);
        $form->handleRequest($this->request);

        if ($form->isSubmitted() && $form->isValid())
        {
            $this->logger->info(sprintf("User %s (ID: %s) has deleted their review (ID: %s).", $user->getUserIdentifier(), $user->getId(), $review->getId()));

            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($review);
            $entityManager->flush();

            $this->addFlash('success', 'Recenze smazána!');

            return $this->redirectToRoute('reviews');
        }

        return $this->render('reviews/review_delete.html.twig', [
            'reviewDeleteForm' => $form->createView(),
            'breadcrumbs' => $this->breadcrumbs->addRoute('review_delete'),
        ]);
    }
}
