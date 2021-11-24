<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\ChangePasswordFormType;
use App\Form\ResetPasswordRequestFormType;
use App\Service\BreadcrumbsService;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;
use SymfonyCasts\Bundle\ResetPassword\Controller\ResetPasswordControllerTrait;
use SymfonyCasts\Bundle\ResetPassword\Exception\ResetPasswordExceptionInterface;
use SymfonyCasts\Bundle\ResetPassword\ResetPasswordHelperInterface;

/**
 * @Route("/obnova-hesla")
 */
class ResetPasswordController extends AbstractController
{
    use ResetPasswordControllerTrait;

    private ResetPasswordHelperInterface $resetPasswordHelper;
    private LoggerInterface $logger;
    private BreadcrumbsService $breadcrumbs;

    public function __construct(ResetPasswordHelperInterface $resetPasswordHelper, LoggerInterface $logger, BreadcrumbsService $breadcrumbs)
    {
        $this->resetPasswordHelper = $resetPasswordHelper;
        $this->logger = $logger;
        $this->breadcrumbs = $breadcrumbs;
    }

    /**
     * Zobrazuje a zpracovává formulář na zažádání o reset hesla
     *
     * @Route("", name="forgot_password_request")
     */
    public function request(Request $request, MailerInterface $mailer): Response
    {
        $options = ['email_empty_data' => ''];
        $user = $this->getUser();
        if ($user)
        {
            $options = [
                'email_empty_data' => $user->getUserIdentifier(), //pokud je uzivatel prihlaseny, doplnime do formulare jeho email
            ];
        }

        $form = $this->createForm(ResetPasswordRequestFormType::class, null, $options);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid())
        {
            return $this->processSendingPasswordResetEmail(
                $form->get('email')->getData(),
                $mailer
            );
        }

        return $this->render('reset_password/request.html.twig', [
            'requestForm' => $form->createView(),
            'breadcrumbs' => $this->breadcrumbs->addRoute('home')->addRoute('login')->addRoute('forgot_password_request'),
        ]);
    }

    /**
     * Potvrzovací stránka po zažádání o reset hesla.
     *
     * @Route("/potvrzeni", name="check_email")
     */
    public function checkEmail(): Response
    {
        // Generate a fake token if the user does not exist or someone hit this page directly.
        // This prevents exposing whether or not a user was found with the given email address or not
        if (null === ($resetToken = $this->getTokenObjectFromSession()))
        {
            $resetToken = $this->resetPasswordHelper->generateFakeResetToken();
        }

        return $this->render('reset_password/check_email.html.twig', [
            'resetToken' => $resetToken,
            'breadcrumbs' => $this->breadcrumbs->addRoute('home')->addRoute('login')->addRoute('forgot_password_request')->addRoute('check_email'),
        ]);
    }

    /**
     * Validace a zpracování resetovací URL, na kterou uživatel kliknul v jeho emailu.
     *
     * @Route("/zpracovani/{token}", name="reset_password")
     */
    public function reset(Request $request, UserPasswordHasherInterface $userPasswordHasherInterface, TranslatorInterface $translator, string $token = null): Response
    {
        if ($token)
        {
            // We store the token in session and remove it from the URL, to avoid the URL being
            // loaded in a browser and potentially leaking the token to 3rd party JavaScript.
            $this->storeTokenInSession($token);

            return $this->redirectToRoute('reset_password');
        }

        $token = $this->getTokenFromSession();
        if (null === $token)
        {
            throw $this->createNotFoundException('Nenalezen platný token na obnovu hesla.');
        }

        try
        {
            $user = $this->resetPasswordHelper->validateTokenAndFetchUser($token);
        }
        catch (ResetPasswordExceptionInterface $e)
        {
            $this->addFlash('failure', $translator->trans($e->getReason()));

            return $this->redirectToRoute('forgot_password_request');
        }

        // The token is valid; allow the user to change their password.
        $form = $this->createForm(ChangePasswordFormType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid())
        {
            // A password reset token should be used only once, remove it.
            $this->resetPasswordHelper->removeResetRequest($token);

            // Encode(hash) the plain password, and set it.
            $encodedPassword = $userPasswordHasherInterface->hashPassword(
                $user,
                $form->get('plainPassword')->get('repeated')->getData()
            );

            $user->setPassword($encodedPassword);
            $user->setIsVerified(true);
            $em = $this->getDoctrine()->getManager();
            $em->persist($user);
            $em->flush();

            // The session is cleaned up after the password has been changed.
            $this->cleanSessionAfterReset();

            $this->addFlash('success', "Vaše heslo bylo úspěšně změněno.");

            $this->logger->info(sprintf("User %s (ID: %s) has changed their password (via email).", $user->getUserIdentifier(), $user->getId()));

            return $this->redirectToRoute('home');
        }

        return $this->render('reset_password/reset.html.twig', [
            'resetForm' => $form->createView(),
            'breadcrumbs' => $this->breadcrumbs->addRoute('home')->addRoute('login')->addRoute('reset_password'),
        ]);
    }

    private function processSendingPasswordResetEmail(string $emailFormData, MailerInterface $mailer): RedirectResponse
    {
        $user = $this->getDoctrine()->getRepository(User::class)->findOneBy([
            'email' => $emailFormData,
        ]);

        // Do not reveal whether a user account was found or not.
        if (!$user)
        {
            return $this->redirectToRoute('check_email');
        }

        try
        {
            $resetToken = $this->resetPasswordHelper->generateResetToken($user);
        }
        catch (ResetPasswordExceptionInterface $e)
        {
            // If you want to tell the user why a reset email was not sent, uncomment
            // the lines below and change the redirect to 'app_forgot_password_request'.
            // Caution: This may reveal if a user is registered or not.
            //
            // $this->addFlash('reset_password_error', sprintf(
            //     'There was a problem handling your password reset request - %s',
            //     $e->getReason()
            // ));

            return $this->redirectToRoute('check_email');
        }

        $email = (new TemplatedEmail())
            ->from(new Address($this->getParameter('app_email_noreply'), $this->getParameter('app_site_name')))
            ->to($user->getEmail())
            ->subject('Změna hesla')
            ->htmlTemplate('reset_password/email.html.twig')
            ->context([
                'resetToken' => $resetToken,
            ])
        ;

        try
        {
            $mailer->send($email);
        }
        catch (TransportExceptionInterface $exception)
        {
            $this->logger->error(sprintf("Someone has requested a password reset email for %s (ID: %s), but the following error occurred in send: %s", $user->getUserIdentifier(), $user->getId(), $exception->getMessage()));
        }

        // Store the token object in session for retrieval in check-email route.
        $this->setTokenObjectInSession($resetToken);

        $this->logger->info(sprintf("Someone has requested a password reset for %s (ID: %s)", $user->getUserIdentifier(), $user->getId()));

        return $this->redirectToRoute('check_email');
    }
}
