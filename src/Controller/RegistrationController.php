<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\RegistrationFormType;
use App\Security\EmailVerifier;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mime\Address;
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

    public function __construct(EmailVerifier $emailVerifier)
    {
        $this->emailVerifier = $emailVerifier;
    }

    /**
     * @Route("/register", name="register")
     */
    public function register(Request $request, UserPasswordHasherInterface $userPasswordHasherInterface, LoginFormAuthenticator $appAuthenticator, UserAuthenticatorInterface $userAuthenticator): Response
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('home');
        }

        $user = new User();
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // encode the plain password
            $user->setPassword(
            $userPasswordHasherInterface->hashPassword(
                    $user,
                    $form->get('plainPassword')->get('repeated')->getData()
                )
            );

            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($user);
            $entityManager->flush();

            // email
            $this->emailVerifier->sendEmailConfirmation('verify_email', $user,
                (new TemplatedEmail())
                    ->from(new Address($this->getParameter('app_email_noreply'), $this->getParameter('app_site_name')))
                    ->to($user->getEmail())
                    ->subject('Aktivace účtu')
                    ->htmlTemplate('registration/confirmation_email.html.twig')
            );

            $this->addFlash('success', 'Byli jste úspěšně zaregistrováni! Svůj účet aktivujete kliknutím na odkaz, který vám byl odeslán na email.');

            return $userAuthenticator->authenticateUser($user, $appAuthenticator, $request, [new RememberMeBadge()]);
        }

        return $this->render('registration/register.html.twig', [
            'registrationForm' => $form->createView(),
        ]);
    }

    /**
     * @Route("/verify-email", name="verify_email")
     *
     * @IsGranted("IS_AUTHENTICATED_FULLY")
     */
    public function verifyUserEmail(Request $request, TranslatorInterface $translator): Response
    {
        try // validate email confirmation link, sets User::isVerified=true and persists
        {
            $this->emailVerifier->handleEmailConfirmation($request, $this->getUser());
        }
        catch (VerifyEmailExceptionInterface $exception)
        {
            $this->addFlash('failure', $translator->trans($exception->getReason()));
            return $this->redirectToRoute('home');
        }

        $this->addFlash('success', 'Vaše e-mailová adresa byla ověřena.');

        return $this->redirectToRoute('home');
    }
}
