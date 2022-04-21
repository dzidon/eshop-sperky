<?php

namespace App\Controller\User;

use App\Entity\Review;
use App\Entity\User;
use App\Form\HiddenTrueFormType;
use App\Form\ReviewFormType;
use App\Form\SearchTextAndSortFormType;
use App\Service\BreadcrumbsService;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormFactoryInterface;
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
    public function reviews(FormFactoryInterface $formFactory): Response
    {
        $form = $formFactory->createNamed('', SearchTextAndSortFormType::class, null, ['sort_choices' => Review::getSortData()]);
        //button je přidáván v šabloně, aby se nezobrazoval v odkazu
        $form->handleRequest($this->request);

        if ($form->isSubmitted() && $form->isValid())
        {
            $pagination = $this->getDoctrine()->getRepository(Review::class)->getSearchPagination($form->get('searchPhrase')->getData(), $form->get('sortBy')->getData());
        }
        else
        {
            $pagination = $this->getDoctrine()->getRepository(Review::class)->getSearchPagination();
        }

        if($pagination->isCurrentPageOutOfBounds())
        {
            throw new NotFoundHttpException('Na této stránce nebyly nalezeny žádné recenze.');
        }

        return $this->render('reviews/reviews_overview.html.twig', [
            'searchForm' => $form->createView(),
            'reviews' => $pagination->getCurrentPageObjects(),
            'pagination' => $pagination->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="review_edit", requirements={"id"="\d+"})
     *
     * @IsGranted("IS_AUTHENTICATED_REMEMBERED")
     */
    public function reviewEdit($id = null): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        if($id !== null) //zadal id do url
        {
            $review = $this->getDoctrine()->getRepository(Review::class)->findOneBy(['id' => $id]);

            if($review === null) //nenaslo to zadnou recenzi
            {
                throw new NotFoundHttpException('Recenze nenalezena.');
            }

            if(!$this->isGranted('review_edit', $review)) //recenzi to naslo, ale nemuze ji editovat ani jako vlastnik a ani jako admin
            {
                throw new AccessDeniedHttpException('Tuto recenzi nemůžete editovat.');
            }

            if ($review->getUser() !== $user && !$this->isGranted('IS_AUTHENTICATED_FULLY')) //admin prihlaseny pres rememberme cookie se snazi upravit cizi recenzi, takze by si mel zopakovat prihlaseni
            {
                throw $this->createAccessDeniedException();
            }

            if ($review->getUser() === $user && !$user->fullNameIsSet())
            {
                $this->addFlash('warning', 'Nemáte nastavené jméno a příjmení, vaše recenze nebude vidět a nijak neovlivní celkové hodnocení.');
            }

            if ($user->isMuted())
            {
                $this->addFlash('warning', 'Jste umlčeni, vaše recenze nebude vidět a nijak neovlivní celkové hodnocení.');
            }

            $this->breadcrumbs->addRoute('review_edit', ['id' => $review->getId()], '', 'edit');
        }
        else //nezadal id do url
        {
            if (!$user->fullNameIsSet())
            {
                $this->addFlash('failure', 'Musíte mít nastavené jméno a příjmení.');
                return $this->redirectToRoute('reviews');
            }

            if($user->getReview() !== null)
            {
                $this->addFlash('failure', 'Můžete napsat pouze jednu recenzi. Protože už jste nějakou napsali, byli jste přesměrováni na úpravu vaší stávající recenze.');
                return $this->redirectToRoute('review_edit', ['id' => $user->getReview()->getId()]);
            }

            if ($user->isMuted())
            {
                $this->addFlash('failure', 'Jste umlčeni, nemůžete napsat recenzi.');
                return $this->redirectToRoute('reviews');
            }

            $review = new Review($user);
            $this->breadcrumbs->addRoute('review_edit', [], '', 'new');
        }

        $form = $this->createForm(ReviewFormType::class, $review);
        $form->add('submit', SubmitType::class, ['label' => 'Uložit']);
        $form->handleRequest($this->request);

        if ($form->isSubmitted() && $form->isValid())
        {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($review);
            $entityManager->flush();

            $this->addFlash('success', 'Recenze uložena!');
            $this->logger->info(sprintf("User %s (ID: %s) has saved a review (ID: %s).", $user->getUserIdentifier(), $user->getId(), $review->getId()));

            return $this->redirectToRoute('reviews');
        }

        return $this->render('reviews/review_edit.html.twig', [
            'review' => $review,
            'reviewForm' => $form->createView(),
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
        else if(!$this->isGranted('review_delete', $review)) //recenzi to naslo, ale nemuze ji smazat ani jako vlastnik a ani jako admin
        {
            throw new AccessDeniedHttpException('Tuto recenzi nemůžete smazat.');
        }

        if ($review->getUser() !== $user && !$this->isGranted('IS_AUTHENTICATED_FULLY')) //admin prihlaseny pres rememberme cookie se snazi smazat recenzi
        {
            throw $this->createAccessDeniedException();
        }

        $form = $this->createForm(HiddenTrueFormType::class, null, ['csrf_token_id' => 'form_review_delete']);
        $form->add('submit',SubmitType::class, [
            'label' => 'Smazat',
            'attr' => [
                'class' => 'btn-large red left',
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

        $this->breadcrumbs->addRoute('review_delete');

        return $this->render('reviews/review_delete.html.twig', [
            'reviewDeleteForm' => $form->createView(),
            'reviewInstance' => $review,
        ]);
    }
}
