<?php

namespace App\Controller\User;

use App\Entity\User;
use App\Form\FormType\User\ChangePasswordFormType;
use App\Form\FormType\User\ResetPasswordRequestFormType;
use App\Service\BreadcrumbsService;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
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

    public function __construct(ResetPasswordHelperInterface $resetPasswordHelper, LoggerInterface $logger, BreadcrumbsService $breadcrumbs)
    {
        $this->resetPasswordHelper = $resetPasswordHelper;
        $this->logger = $logger;
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
    public function request(MailerInterface $mailer, Request $request): Response
    {
        $userForEmailValidation = new User();
        $form = $this->createForm(ResetPasswordRequestFormType::class, $userForEmailValidation);
        $form->add('submit', SubmitType::class, ['label' => 'Poslat odkaz']);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid())
        {
            return $this->processSendingPasswordResetEmail(
                $userForEmailValidation->getEmail(),
                $mailer
            );
        }

        return $this->render('reset_password/request.html.twig', [
            'requestForm' => $form->createView(),
        ]);
    }

    /**
     * Validace a zpracování resetovací URL, na kterou uživatel kliknul v jeho emailu.
     *
     * @Route("/zpracovani/{token}", name="reset_password")
     */
    public function reset(TranslatorInterface $translator, Request $request, string $token = null): Response
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
        $form->handleRequest($request);

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

        $this->breadcrumbs->addRoute('reset_password');

        return $this->render('reset_password/reset.html.twig', [
            'resetForm' => $form->createView(),
        ]);
    }

    private function processSendingPasswordResetEmail(string $emailFormData, MailerInterface $mailer): RedirectResponse
    {
        $user = $this->getDoctrine()->getRepository(User::class)->findOneBy([
            'email' => $emailFormData,
        ]);

        if (!$user)
        {
            $this->resetPasswordHelper->generateFakeResetToken();
            return $this->redirectWithSuccessMessage();
        }

        try
        {
            $resetToken = $this->resetPasswordHelper->generateResetToken($user);
        }
        catch (ResetPasswordExceptionInterface $e)
        {
            $this->resetPasswordHelper->generateFakeResetToken();
            return $this->redirectWithSuccessMessage();
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
            $this->logger->info(sprintf("Someone has requested a password reset for %s (ID: %s)", $user->getUserIdentifier(), $user->getId()));
        }
        catch (TransportExceptionInterface $exception)
        {
            $this->logger->error(sprintf("Someone has requested a password reset email for %s (ID: %s), but the following error occurred in send: %s", $user->getUserIdentifier(), $user->getId(), $exception->getMessage()));
        }

        $this->setTokenObjectInSession($resetToken);
        return $this->redirectWithSuccessMessage();
    }

    private function redirectWithSuccessMessage(): RedirectResponse
    {
        $this->addFlash('success', 'Pokud vámi zadaný email existuje, poslali jsme na něj odkaz, přes který si můžete resetovat heslo. Tento odkaz vyprší za 1 hodinu. Pokud email neobdržíte, zkontrolujte SPAM složku nebo to zkuste znovu.');
        return $this->redirectToRoute('forgot_password_request');
    }
}