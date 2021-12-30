<?php

namespace App\Controller;

use App\Entity\Review;
use App\Form\ReviewDeleteFormType;
use App\Form\ReviewFormType;
use App\Service\BreadcrumbsService;
use App\Service\PaginatorService;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
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
     * @Route("/vsechny", name="reviews")
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
     * @Route("/{id}", name="review_edit", requirements={"id"="\d+"})
     *
     * @IsGranted("IS_AUTHENTICATED_REMEMBERED")
     */
    public function reviewEdit($id = null): Response
    {
        $user = $this->getUser();
        if (!$user->isVerified())
        {
            $this->addFlash('failure', 'Nemáte ověřený účet.');
            return $this->redirectToRoute('reviews');
        }
        if ($user->getNameFirst() === null || $user->getNameLast() === null)
        {
            $this->addFlash('failure', 'Musíte mít nastavené jméno a příjmení.');
            return $this->redirectToRoute('reviews');
        }

        if($id !== null) //zadal id do url
        {
            $review = $this->getDoctrine()->getRepository(Review::class)->findOneBy(['id' => $id]);

            if($review === null) //nenaslo to zadnou recenzi
            {
                throw new NotFoundHttpException('Recenze nenalezena.');
            }
            else if(!$this->isGranted('review_edit', $review)) //recenzi to naslo, jenze patri jinemu uzivateli
            {
                throw new AccessDeniedHttpException('Tuto recenzi nemůžete editovat.');
            }

            $this->breadcrumbs->addRoute('review_edit', ['id' => $review->getId()], '', 'edit');
        }
        else if($user->getReview() !== null) //nezadal id do url, ale už napsal recenzi, nemůže přidat další
        {
            $this->addFlash('failure', 'Můžete napsat pouze jednu recenzi. Protože už jste nějakou napsali, byli jste přesměrováni na úpravu vaší stávající recenze.');
            return $this->redirectToRoute('review_edit', ['id' => $user->getReview()->getId()]);
        }
        else //nezadal id do url + jeste nenapsal zadnou recenzi, takže vytvari novou recenzi
        {
            $review = $this->getDoctrine()->getRepository(Review::class)->createNew($user);
            $this->breadcrumbs->addRoute('review_edit', [], '', 'new');
        }

        if ($review->getUser() !== null && $review->getUser() !== $user && !$this->isGranted('IS_AUTHENTICATED_FULLY')) //admin prihlaseny pres rememberme cookie se snazi upravit cizi recenzi
        {
            throw $this->createAccessDeniedException();
        }

        $form = $this->createForm(ReviewFormType::class, $review);
        $form->add('submit', SubmitType::class, ['label' => 'Uložit']);
        $form->handleRequest($this->request);

        if ($form->isSubmitted() && $form->isValid())
        {
            if($review->getId() !== null)
            {
                $review->setUpdated(new \DateTime('now'));
            }

            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($review);
            $entityManager->flush();

            $this->addFlash('success', 'Recenze uložena!');
            $this->logger->info(sprintf("User %s (ID: %s) has saved a review (ID: %s).", $user->getUserIdentifier(), $user->getId(), $review->getId()));

            return $this->redirectToRoute('reviews');
        }

        return $this->render('reviews/review_edit.html.twig', [
            'reviewForm' => $form->createView(),
            'breadcrumbs' => $this->breadcrumbs,
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
        $review = $this->getDoctrine()->getRepository(Review::class)->findOneBy(['id' => $id]);

        if($review === null) //nenaslo to zadnou recenzi
        {
            throw new NotFoundHttpException('Recenze nenalezena.');
        }
        else if(!$this->isGranted('review_delete', $review)) //recenzi to naslo, jenze patri jinemu uzivateli
        {
            throw new AccessDeniedHttpException('Tuto recenzi nemůžete smazat.');
        }

        if ($review->getUser() !== $user && !$this->isGranted('IS_AUTHENTICATED_FULLY')) //admin prihlaseny pres rememberme cookie se snazi smazat recenzi
        {
            throw $this->createAccessDeniedException();
        }

        $form = $this->createForm(ReviewDeleteFormType::class);
        $form->add('submit',SubmitType::class, [
            'label' => 'Smazat',
            'attr' => [
                'class' => 'waves-effect waves-light btn-large red',
            ],
        ]);
        $form->handleRequest($this->request);

        if ($form->isSubmitted() && $form->isValid())
        {
            $this->logger->info(sprintf("User %s (ID: %s) has deleted a review (ID: %s).", $user->getUserIdentifier(), $user->getId(), $review->getId()));

            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($review);
            $entityManager->flush();

            $this->addFlash('success', 'Recenze smazána!');

            return $this->redirectToRoute('reviews');
        }

        return $this->render('reviews/review_delete.html.twig', [
            'reviewDeleteForm' => $form->createView(),
            'reviewInstance' => $review,
            'breadcrumbs' => $this->breadcrumbs->addRoute('review_delete'),
        ]);
    }
}
