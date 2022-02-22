<?php

namespace App\Controller\User;

use App\Entity\User;
use App\Form\ChangePasswordFormType;
use App\Form\ResetPasswordRequestFormType;
use App\Service\BreadcrumbsService;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
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
    private $request;

    public function __construct(ResetPasswordHelperInterface $resetPasswordHelper, LoggerInterface $logger, BreadcrumbsService $breadcrumbs, RequestStack $requestStack)
    {
        $this->resetPasswordHelper = $resetPasswordHelper;
        $this->logger = $logger;
        $this->request = $requestStack->getCurrentRequest();
        $this->breadcrumbs = $breadcrumbs
            ->addRoute('home')
            ->addRoute('login')
            ->addRoute('forgot_password_request');
    }

    /**
     * Zobrazuje a zpracovává formulář na zažádání o reset hesla
     *
     * @Route("", name="forgot_password_request")
     */
    public function request(MailerInterface $mailer): Response
    {
        $userForEmailValidation = new User();
        $form = $this->createForm(ResetPasswordRequestFormType::class, $userForEmailValidation);
        $form->add('submit', SubmitType::class, ['label' => 'Poslat odkaz']);
        $form->handleRequest($this->request);

        if ($form->isSubmitted() && $form->isValid())
        {
            return $this->processSendingPasswordResetEmail(
                $userForEmailValidation->getEmail(),
                $mailer
            );
        }

        return $this->render('reset_password/request.html.twig', [
            'requestForm' => $form->createView(),
            'breadcrumbs' => $this->breadcrumbs,
        ]);
    }

    /**
     * Potvrzovací stránka po zažádání o reset hesla.
     *
     * @Route("/potvrzeni", name="check_email")
     */
    public function checkEmail(): Response
    {
        if (null === ($resetToken = $this->getTokenObjectFromSession()))
        {
            $resetToken = $this->resetPasswordHelper->generateFakeResetToken();
        }

        return $this->render('reset_password/check_email.html.twig', [
            'resetToken' => $resetToken,
            'breadcrumbs' => $this->breadcrumbs->addRoute('check_email'),
        ]);
    }

    /**
     * Validace a zpracování resetovací URL, na kterou uživatel kliknul v jeho emailu.
     *
     * @Route("/zpracovani/{token}", name="reset_password")
     */
    public function reset(TranslatorInterface $translator, string $token = null): Response
    {
        if ($token)
        {
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

        $form = $this->createForm(ChangePasswordFormType::class, $user);
        $form->add('submit', SubmitType::class, ['label' => 'Změnit']);
        $form->handleRequest($this->request);

        if ($form->isSubmitted() && $form->isValid())
        {
            $this->resetPasswordHelper->removeResetRequest($token);
            $user->setIsVerified(true);

            $em = $this->getDoctrine()->getManager();
            $em->persist($user);
            $em->flush();

            $this->cleanSessionAfterReset();

            $this->logger->info(sprintf("User %s (ID: %s) has changed their password (via email).", $user->getUserIdentifier(), $user->getId()));
            if($this->getUser())
            {
                $this->addFlash('success', "Heslo bylo úspěšně změněno.");
                return $this->redirectToRoute('home');
            }
            else
            {
                $this->addFlash('success', "Heslo bylo úspěšně změněno. Nyní se s ním můžete přihlásit.");
                return $this->redirectToRoute('login');
            }
        }

        return $this->render('reset_password/reset.html.twig', [
            'resetForm' => $form->createView(),
            'breadcrumbs' => $this->breadcrumbs->addRoute('reset_password'),
        ]);
    }

    private function processSendingPasswordResetEmail(string $emailFormData, MailerInterface $mailer): RedirectResponse
    {
        $user = $this->getDoctrine()->getRepository(User::class)->findOneBy([
            'email' => $emailFormData,
        ]);

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
            ->htmlTemplate('fragments/emails/_reset_password.html.twig')
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

        $this->setTokenObjectInSession($resetToken);
        $this->logger->info(sprintf("Someone has requested a password reset for %s (ID: %s)", $user->getUserIdentifier(), $user->getId()));

        return $this->redirectToRoute('check_email');
    }
}
