<?php

namespace App\Controller\User;

use App\Entity\Detached\ContactEmail;
use App\Entity\Product;
use App\Entity\Review;
use App\Form\ContactFormType;
use App\Service\BreadcrumbsService;
use App\Service\ContactEmailService;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Routing\Annotation\Route;

class MainController extends AbstractController
{
    private BreadcrumbsService $breadcrumbs;
    private ParameterBagInterface $parameterBag;

    public function __construct(BreadcrumbsService $breadcrumbs, ParameterBagInterface $parameterBag)
    {
        $this->breadcrumbs = $breadcrumbs->addRoute('home');
        $this->parameterBag = $parameterBag;
    }

    /**
     * @Route("/", name="home")
     */
    public function index(BreadcrumbsService $breadcrumbs): Response
    {
        $latestProducts = $this->getDoctrine()->getRepository(Product::class)->findLatest(4);
        $latestReviews = $this->getDoctrine()->getRepository(Review::class)->findLatest(4);
        $totalAndAverageRating = $this->getDoctrine()->getRepository(Review::class)->getTotalAndAverage();

        return $this->render('main/index.html.twig', [
            'latestProducts' => $latestProducts,
            'latestReviews' => $latestReviews,
            'totalAndAverageRating' => $totalAndAverageRating,
            'breadcrumbs' => $breadcrumbs,
        ]);
    }

    /**
     * @Route("/kontakt", name="contact")
     */
    public function contact(Request $request, LoggerInterface $logger, ContactEmailService $contactEmailService): Response
    {
        $emailData = new ContactEmail();
        $form = $this->createForm(ContactFormType::class, $emailData);
        $form->add('submit', SubmitType::class, ['label' => 'Odeslat']);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid())
        {
            try
            {
                $contactEmailService
                    ->initialize($emailData)
                    ->send();

                $this->addFlash('success', 'E-mail odeslán, brzy se ozveme!');
                $logger->info(sprintf("Someone has sent a contact email with a subject '%s' from %s.", $contactEmailService->getSubject(), $contactEmailService->getSenderEmail()));

                return $this->redirectToRoute('contact');
            }
            catch (TransportExceptionInterface $exception)
            {
                $this->addFlash('failure', 'E-mail se nepodařilo odeslat, zkuste to znovu.');
                $logger->error(sprintf("Someone has tried to send a contact email from %s, but the following error occurred in send: %s", $contactEmailService->getSenderEmail(), $exception->getMessage()));
            }
        }

        return $this->render('main/contact.html.twig', [
            'contactForm' => $form->createView(),
            'breadcrumbs' => $this->breadcrumbs->addRoute('contact'),
        ]);
    }
}