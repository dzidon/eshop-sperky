<?php

namespace App\Controller\User;

use App\Entity\Detached\ContactEmail;
use App\Entity\Product;
use App\Entity\Review;
use App\Form\ContactFormType;
use App\Form\CustomOrderDemandFormType;
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
    public function index(): Response
    {
        $latestProducts = $this->getDoctrine()->getRepository(Product::class)->findLatest(4);
        $latestReviews = $this->getDoctrine()->getRepository(Review::class)->findLatest(4);
        $totalAndAverageRating = $this->getDoctrine()->getRepository(Review::class)->getTotalAndAverage();

        return $this->render('main/index.html.twig', [
            'latestProducts' => $latestProducts,
            'latestReviews' => $latestReviews,
            'totalAndAverageRating' => $totalAndAverageRating,
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
                $contactEmailService->send($emailData);
                $this->addFlash('success', sprintf('E-mail odesl??n, brzy se ozveme na %s!', $emailData->getEmail()));

                return $this->redirectToRoute('contact');
            }
            catch (TransportExceptionInterface $exception)
            {
                $this->addFlash('failure', 'E-mail se nepoda??ilo odeslat, zkuste to znovu.');
                $logger->error(sprintf("Someone has tried to send a contact email as %s, but the following error occurred in send: %s", $emailData->getEmail(), $exception->getMessage()));
            }
        }

        $this->breadcrumbs->addRoute('contact');

        return $this->render('main/contact.html.twig', [
            'contactForm' => $form->createView(),
        ]);
    }

    /**
     * @Route("/objednavka-na-miru", name="order_custom_new")
     */
    public function orderCustomNew(Request $request, LoggerInterface $logger, ContactEmailService $contactEmailService): Response
    {
        $emailData = new ContactEmail();
        $emailData->setSubject('Objedn??vka na m??ru');

        $form = $this->createForm(CustomOrderDemandFormType::class, $emailData);
        $form->add('submit', SubmitType::class, ['label' => 'Odeslat']);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid())
        {
            try
            {
                $contactEmailService->send($emailData);
                $this->addFlash('success', sprintf('Nez??vazn?? popt??vka odesl??na, brzy se ozveme na %s!', $emailData->getEmail()));

                return $this->redirectToRoute('order_custom_new');
            }
            catch (TransportExceptionInterface $exception)
            {
                $this->addFlash('failure', 'E-mail se nepoda??ilo odeslat, zkuste to znovu.');
                $logger->error(sprintf("Someone has tried to send a contact email as %s, but the following error occurred in send: %s", $emailData->getEmail(), $exception->getMessage()));
            }
        }

        $this->breadcrumbs->addRoute('order_custom_new');

        return $this->render('main/order_custom_new.html.twig', [
            'contactForm' => $form->createView(),
        ]);
    }
}