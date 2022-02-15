<?php

namespace App\Controller\User;

use App\Entity\User;
use App\Form\LoginFormType;
use App\Form\RegistrationFormType;
use App\Security\EmailVerifier;
use App\Service\BreadcrumbsService;
use DateTime;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Core\Security;
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
        $this->breadcrumbs = $breadcrumbs;
        $this->request = $requestStack->getCurrentRequest();
    }

    /**
     * @Route("/registrace", name="register")
     */
    public function register(UserPasswordHasherInterface $userPasswordHasherInterface, ParameterBagInterface $parameterBag): Response
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
            //možná už existuje uživatel s daným emailem
            $existingUser = $this->getDoctrine()->getRepository(User::class)->findOneBy(['email' => $user->getEmail()]);

            // zasifrovani hesla
            $user->setPassword(
            $userPasswordHasherInterface->hashPassword(
                    $user,
                    $user->getPlainPassword(),
                )
            );

            // pokud neexistuje jiny uzivatel s danym emailem v db, muzeme aktualniho uzivatele ulozit,
            // pokud existuje jiny uzivatel s danym emailem, ktery jeste neni overeny a muze si nechat poslat novy link,
            // posleme mu ho a znovu mu nastavime zadavane heslo
            $entityManager = $this->getDoctrine()->getManager();
            $userForEmailConfirmation = null;
            $now = new DateTime('now');
            if($existingUser === null)
            {
                $user->setVerifyLinkLastSent($now);
                $entityManager->persist($user);
                $entityManager->flush();
                $userForEmailConfirmation = $user;

                $this->logger->info(sprintf("User %s (ID: %s) has registered a new account using email & password.", $user->getUserIdentifier(), $user->getId()));
            }
            else if(!$existingUser->isVerified() && $existingUser->canSendAnotherVerifyLink($parameterBag->get('app_email_verify_link_throttle_limit')))
            {
                $existingUser->setVerifyLinkLastSent($now);
                $existingUser->setRegistered($now);
                $existingUser->setPassword( $user->getPassword() );
                $entityManager->flush();
                $userForEmailConfirmation = $existingUser;

                $this->logger->info(sprintf("Someone has tried to register with the following e-mail: %s. This e-mail is already assigned to an unverified account ID %s. A new verification link has been sent.", $user->getEmail(), $existingUser->getId()));
            }

            // email
            try
            {
                $this->emailVerifier->sendEmailConfirmation('verify_email', $userForEmailConfirmation);
            }
            catch (TransportExceptionInterface $exception)
            {
                $this->logger->error(sprintf("User %s has tried to register, but the following error occurred in sendEmailConfirmation: %s", $user->getUserIdentifier(), $exception->getMessage()));
            }

            //uživatel už je ověřený nebo je moc brzo na další ověřovací odkaz
            if(!$userForEmailConfirmation)
            {
                $this->logger->error(sprintf("User %s has tried to register, but this email is already verified or it's too soon for another verification link.", $user->getUserIdentifier()));
            }

            $this->addFlash('success', sprintf("Registrace proběhla úspěšně! Pokud zadaný e-mail %s existuje, poslali jsme na něj potvrzovací odkaz, přes který e-mail ověříte. Pokud potvrzovací odkaz nedorazí, zkuste registraci za 10 minut opakovat.", $user->getEmail()));
            return $this->redirectToRoute('home');
        }

        return $this->render('authentication/register.html.twig', [
            'registrationForm' => $form->createView(),
            'breadcrumbs' => $this->breadcrumbs->addRoute('home')->addRoute('register'),
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

        if(!$this->request->query->has('expires') || !$this->request->query->has('signature') || !$this->request->query->has('token'))
        {
            throw new NotFoundHttpException('Nemáte platný odkaz pro ověření účtu.');
        }

        $error = $authenticationUtils->getLastAuthenticationError();
        if($error)
        {
            $this->addFlash('failure', $this->translator->trans($error->getMessageKey()));
        }

        $form = $formFactory->createNamed('', LoginFormType::class, null, ['csrf_token_id' => 'form_verification', 'last_email' => $authenticationUtils->getLastUsername()]);
        $form->add('submit', SubmitType::class, ['label' => 'Ověřit']);

        $this->request->getSession()->remove(Security::LAST_USERNAME);

        return $this->render('authentication/verification.html.twig', [
            'verificationForm' => $form->createView(),
            'breadcrumbs' => $this->breadcrumbs->addRoute('home')->addRoute('register')->addRoute('verify_email'),
        ]);
    }
}