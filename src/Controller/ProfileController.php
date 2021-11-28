<?php

namespace App\Controller;

use App\Form\ChangePasswordLoggedInFormType;
use App\Form\PersonalInfoFormType;
use App\Form\SendEmailToVerifyFormType;
use App\Security\EmailVerifier;
use App\Service\BreadcrumbsService;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @Route("/profil")
 *
 * @IsGranted("IS_AUTHENTICATED_REMEMBERED")
 */
class ProfileController extends AbstractController
{
    private LoggerInterface $logger;
    private BreadcrumbsService $breadcrumbs;
    private $request;

    public function __construct(LoggerInterface $logger, BreadcrumbsService $breadcrumbs, RequestStack $requestStack)
    {
        $this->logger = $logger;
        $this->breadcrumbs = $breadcrumbs;
        $this->request = $requestStack->getCurrentRequest();

        $this->breadcrumbs->addRoute('home')->addRoute('profile');
    }

    /**
     * @Route("", name="profile")
     */
    public function overview(): Response
    {
        $user = $this->getUser();
        $formView = null;

        if($user->isVerified())
        {
            $form = $this->createForm(PersonalInfoFormType::class, $user);
            $form->handleRequest($this->request);

            if ($form->isSubmitted() && $form->isValid())
            {
                $entityManager = $this->getDoctrine()->getManager();
                $entityManager->persist($user);
                $entityManager->flush();

                $this->addFlash('success', 'Osobní údaje uloženy!');
                $this->logger->info(sprintf("User %s (ID: %s) has changed their personal information.", $user->getUserIdentifier(), $user->getId()));

                return $this->redirectToRoute('profile');
            }

            $formView = $form->createView();
        }

        return $this->render('profile/profile_overview.html.twig', [
            'personalDataForm' => $formView,
            'breadcrumbs' => $this->breadcrumbs->setPageTitleByRoute('profile'),
        ]);
    }

    /**
     * @Route("/zmena-hesla", name="profile_change_password")
     */
    public function passwordChange(UserPasswordHasherInterface $userPasswordHasherInterface): Response
    {
        if ($this->getUser()->getPassword() === null)
        {
            $this->addFlash('failure', 'Na tomto účtu nemáte nastavené heslo, takže si ho musíte změnit přes email.');
            return $this->redirectToRoute('forgot_password_request');
        }

        $form = $this->createForm(ChangePasswordLoggedInFormType::class);
        $form->handleRequest($this->request);

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
            'breadcrumbs' => $this->breadcrumbs->setPageTitleByRoute('profile_change_password'),
        ]);
    }

    /**
     * @Route("/overeni-emailu", name="profile_verify")
     */
    public function verify(EmailVerifier $emailVerifier, TranslatorInterface $translator): Response
    {
        $user = $this->getUser();
        if ($user->isVerified())
        {
            $this->addFlash('failure', 'Váš email už je ověřený.');
            return $this->redirectToRoute('home');
        }

        $form = $this->createForm(SendEmailToVerifyFormType::class);
        $form->handleRequest($this->request);

        if ($form->isSubmitted() && $form->isValid())
        {
            try
            {
                $emailVerifier->sendEmailConfirmation('verify_email', $user);
                $this->addFlash('success', 'E-mail odeslán!');
                $this->logger->info(sprintf("User %s (ID: %s) has requested a new email verification link.", $user->getUserIdentifier(), $user->getId()));
            }
            catch (\Exception | TransportExceptionInterface $exception)
            {
                $this->addFlash('failure', $translator->trans($exception->getMessage()));
            }
            return $this->redirectToRoute('profile_verify');
        }

        return $this->render('profile/profile_verify.html.twig', [
            'sendAgainForm' => $form->createView(),
            'breadcrumbs' => $this->breadcrumbs->setPageTitleByRoute('profile_verify'),
        ]);
    }
}