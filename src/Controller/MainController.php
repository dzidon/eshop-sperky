<?php

namespace App\Controller;

use App\Entity\Review;
use App\Form\ContactFormType;
use App\Service\BreadcrumbsService;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
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
        $latestReviews = $this->getDoctrine()->getRepository(Review::class)->findLatest(4);
        $totalAndAverage = $this->getDoctrine()->getRepository(Review::class)->getTotalAndAverage();

        return $this->render('main/index.html.twig', [
            'agregateReviewData' => $totalAndAverage,
            'latestReviews' => $latestReviews,
            'breadcrumbs' => $breadcrumbs,
        ]);
    }

    /**
     * @Route("/kontakt", name="contact")
     */
    public function contact(Request $request, LoggerInterface $logger, MailerInterface $mailer): Response
    {
        $form = $this->createForm(ContactFormType::class, null, ['email_empty_data' => ($this->getUser() === null ? '' : $this->getUser()->getUserIdentifier()) ]);
        $form->add('submit', SubmitType::class, ['label' => 'Odeslat']);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid())
        {
            $senderEmail = $form->get('email')->getData();
            $recipientEmail = $this->parameterBag->get('app_site_email');
            $subject = $form->get('subject')->getData();
            $text = $form->get('text')->getData();

            $email = new Email();
            $email->from(new Address($senderEmail))
                ->to($recipientEmail)
                ->subject($subject)
                ->text($text);

            try
            {
                $mailer->send($email);
                $this->addFlash('success', 'E-mail odeslán, brzy se ozveme!');
                $logger->info(sprintf("Someone has sent a contact email as %s.", $senderEmail));

                return $this->redirectToRoute('contact');
            }
            catch (TransportExceptionInterface $exception)
            {
                $this->addFlash('failure', 'E-mail se nepodařilo odeslat, zkuste to znovu.');
                $logger->error(sprintf("Someone has tried to send a contact email as %s, but the following error occurred in send: %s", $senderEmail, $exception->getMessage()));
            }
        }

        return $this->render('main/contact.html.twig', [
            'contactForm' => $form->createView(),
            'breadcrumbs' => $this->breadcrumbs->addRoute('contact'),
        ]);
    }
}
