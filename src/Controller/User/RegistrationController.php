<?php

namespace App\Controller\User;

use App\Entity\User;
use App\Form\RegistrationFormType;
use App\Form\VerificationFormType;
use App\Security\EmailVerifier;
use App\Service\BreadcrumbsService;
use App\Service\UserRegistrationService;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;

class RegistrationController extends AbstractController
{
    private EmailVerifier $emailVerifier;
    private LoggerInterface $logger;
    private TranslatorInterface $translator;
    private BreadcrumbsService $breadcrumbs;
    private $request;

    public function __construct(EmailVerifier $emailVerifier, LoggerInterface $logger, TranslatorInterface $translator, BreadcrumbsService $breadcrumbs, RequestStack $requestStack)
    {
        $this->emailVerifier = $emailVerifier;
        $this->logger = $logger;
        $this->translator = $translator;
        $this->breadcrumbs = $breadcrumbs->addRoute('home')->addRoute('register');
        $this->request = $requestStack->getCurrentRequest();
    }

    /**
     * @Route("/registrace", name="register")
     */
    public function register(UserRegistrationService $userRegistrationService): Response
    {
        if ($this->getUser())
        {
            $this->addFlash('failure', 'Už jste přihlášen. Pro zaregistrování se nejdříve odhlašte.');
            return $this->redirectToRoute('home');
        }

        $user = new User();
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->add('submit', SubmitType::class, ['label' => 'Zaregistrovat se']);
        $form->handleRequest($this->request);

        if ($form->isSubmitted() && $form->isValid())
        {
            $userRegistrationService->register($user);
            $this->addFlash('success', sprintf("Registrace proběhla úspěšně! Pokud zadaný e-mail %s existuje, poslali jsme na něj potvrzovací odkaz, přes který e-mail ověříte. Pokud potvrzovací odkaz nedorazí, zkuste registraci za 5 minut opakovat.", $user->getEmail()));

            return $this->redirectToRoute('home');
        }

        return $this->render('authentication/register.html.twig', [
            'registrationForm' => $form->createView(),
        ]);
    }

    /**
     * @Route("/overeni-emailu", name="verify_email")
     */
    public function verifyUserEmail(FormFactoryInterface $formFactory, AuthenticationUtils $authenticationUtils): Response
    {
        $user = $this->getUser();
        if($user)
        {
            $this->addFlash('failure', 'Už jste přihlášen. Pro ověření účtu se nejdříve odhlašte.');
            return $this->redirectToRoute('home');
        }

        if(!$this->request->query->has('email') || !$this->request->query->has('expires') || !$this->request->query->has('signature') || !$this->request->query->has('token'))
        {
            throw new NotFoundHttpException('Nemáte platný odkaz pro ověření účtu.');
        }

        $error = $authenticationUtils->getLastAuthenticationError();
        if($error)
        {
            $this->addFlash('failure', $this->translator->trans($error->getMessageKey()));
        }

        $form = $formFactory->createNamed('', VerificationFormType::class, null, ['default_email' => $this->request->query->get('email', '')]);
        $form->add('submit', SubmitType::class, ['label' => 'Ověřit']);

        $this->breadcrumbs->addRoute('verify_email');

        return $this->render('authentication/verification.html.twig', [
            'verificationForm' => $form->createView(),
        ]);
    }
}