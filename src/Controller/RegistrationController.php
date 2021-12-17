<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\RegistrationFormType;
use App\Security\EmailVerifier;
use App\Service\BreadcrumbsService;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\RememberMeBadge;
use Symfony\Contracts\Translation\TranslatorInterface;
use SymfonyCasts\Bundle\VerifyEmail\Exception\VerifyEmailExceptionInterface;
use App\Security\LoginFormAuthenticator;
use Symfony\Component\Security\Http\Authentication\UserAuthenticatorInterface;
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
        $this->breadcrumbs = $breadcrumbs;
        $this->request = $requestStack->getCurrentRequest();
    }

    /**
     * @Route("/registrace", name="register")
     */
    public function register(UserPasswordHasherInterface $userPasswordHasherInterface, LoginFormAuthenticator $appAuthenticator, UserAuthenticatorInterface $userAuthenticator): Response
    {
        if ($this->getUser())
        {
            $this->addFlash('failure', 'Už jste přihlášen. Pro zaregistrování se nejdříve odhlašte.');
            return $this->redirectToRoute('home');
        }

        $user = new User();
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($this->request);

        if ($form->isSubmitted() && $form->isValid())
        {
            // zasifrovani hesla
            $user->setPassword(
            $userPasswordHasherInterface->hashPassword(
                    $user,
                    $form->get('plainPassword')->getData()
                )
            );

            $user->setRegistered(new \DateTime('now'));
            $user->setGender(false);

            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($user);
            $entityManager->flush();

            // email
            try
            {
                $this->emailVerifier->sendEmailConfirmation('verify_email', $user, true);
            }
            catch (\Exception | TransportExceptionInterface $exception)
            {
                $this->logger->error(sprintf("User %s (ID: %s) has registered, but the following error occurred in sendEmailConfirmation: %s", $user->getUserIdentifier(), $user->getId(), $exception->getMessage()));
            }

            return $userAuthenticator->authenticateUser($user, $appAuthenticator->setJustRegistered(true), $this->request, [new RememberMeBadge()]);
        }

        return $this->render('registration/register.html.twig', [
            'registrationForm' => $form->createView(),
            'breadcrumbs' => $this->breadcrumbs->addRoute('home')->addRoute('register'),
        ]);
    }

    /**
     * @Route("/overeni-emailu", name="verify_email")
     *
     * @IsGranted("IS_AUTHENTICATED_REMEMBERED")
     */
    public function verifyUserEmail(): Response
    {
        $user = $this->getUser();

        try // validate email confirmation link, sets User::isVerified=true and persists
        {
            $this->emailVerifier->handleEmailConfirmation($this->request, $user);
        }
        catch (VerifyEmailExceptionInterface $exception)
        {
            $this->addFlash('failure', $this->translator->trans($exception->getReason()));
            return $this->redirectToRoute('home');
        }

        $this->addFlash('success', 'Vaše e-mailová adresa byla ověřena.');

        $this->logger->info(sprintf("User %s (ID: %s) has verified their email.", $user->getUserIdentifier(), $user->getId()));

        return $this->redirectToRoute('profile');
    }
}
