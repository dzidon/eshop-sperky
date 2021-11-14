<?php

namespace App\Controller;

use App\Form\ChangePasswordLoggedInFormType;
use App\Form\SendEmailToVerifyFormType;
use App\Security\EmailVerifier;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;

/**
 * @Route("/profile")
 *
 * @IsGranted("IS_AUTHENTICATED_REMEMBERED")
 */
class ProfileController extends AbstractController
{
    private LoggerInterface $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * @Route("", name="profile")
     */
    public function overview(): Response
    {
        return $this->render('profile/profile_overview.html.twig');
    }

    /**
     * @Route("/change-password", name="profile_change_password")
     */
    public function passwordChange(Request $request, UserPasswordHasherInterface $userPasswordHasherInterface): Response
    {
        if ($this->getUser()->getPassword() === null)
        {
            $this->addFlash('failure', 'Na tomto účtu nemáte nastavené heslo, takže si ho musíte změnit přes email.');
            return $this->redirectToRoute('forgot_password_request');
        }

        $form = $this->createForm(ChangePasswordLoggedInFormType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid())
        {
            $user = $this->getUser();
            $user->setPassword(
                $userPasswordHasherInterface->hashPassword(
                    $user,
                    $form->get('newPlainPassword')->get('repeated')->getData()
                )
            );

            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($user);
            $entityManager->flush();

            $this->addFlash('success', 'Heslo změněno!');

            $this->logger->info(sprintf("User %s (ID: %s) has changed their password (via profile).", $user->getUserIdentifier(), $user->getId()));

            return $this->redirectToRoute('profile_change_password');
        }

        return $this->render('profile/profile_change_password.html.twig', [
            'changeForm' => $form->createView(),
        ]);
    }

    /**
     * @Route("/verify-email", name="profile_verify")
     */
    public function verify(Request $request, EmailVerifier $emailVerifier): Response
    {
        $user = $this->getUser();
        if ($user->isVerified())
        {
            $this->addFlash('failure', 'Váš email už je ověřený.');
            return $this->redirectToRoute('home');
        }

        $form = $this->createForm(SendEmailToVerifyFormType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid())
        {
            $emailVerifier->sendEmailConfirmation('verify_email', $user);

            $this->addFlash('success', 'E-mail odeslán!');
            $this->logger->info(sprintf("User %s (ID: %s) has requested a new email verification link.", $user->getUserIdentifier(), $user->getId()));

            return $this->redirectToRoute('profile_verify');
        }

        return $this->render('profile/profile_verify.html.twig', [
            'sendAgainForm' => $form->createView(),
        ]);
    }
}
