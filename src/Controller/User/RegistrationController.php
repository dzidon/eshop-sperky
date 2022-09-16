<?php

namespace App\Controller\User;

use App\Entity\User;
use App\Form\FormType\User\RegistrationFormType;
use App\Form\FormType\User\VerificationFormType;
use App\Service\BreadcrumbsService;
use App\Service\UserRegistrationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;

class RegistrationController extends AbstractController
{
    private TranslatorInterface $translator;
    private BreadcrumbsService $breadcrumbs;

    public function __construct(TranslatorInterface $translator, BreadcrumbsService $breadcrumbs)
    {
        $this->translator = $translator;
        $this->breadcrumbs = $breadcrumbs->addRoute('home')->addRoute('register');
    }

    /**
     * @Route("/registrace", name="register")
     */
    public function register(UserRegistrationService $userRegistrationService, Request $request): Response
    {
        if ($this->getUser())
        {
            $this->addFlash('failure', 'Už jste přihlášen. Pro zaregistrování se nejdříve odhlašte.');
            return $this->redirectToRoute('home');
        }

        $user = new User();
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->add('submit', SubmitType::class, ['label' => 'Zaregistrovat se']);
        $form->handleRequest($request);

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
    public function verifyUserEmail(FormFactoryInterface $formFactory, AuthenticationUtils $authenticationUtils, Request $request): Response
    {
        $user = $this->getUser();
        if($user)
        {
            $this->addFlash('failure', 'Už jste přihlášen. Pro ověření účtu se nejdříve odhlašte.');
            return $this->redirectToRoute('home');
        }

        if(!$request->query->has('email') || !$request->query->has('expires') || !$request->query->has('signature') || !$request->query->has('token'))
        {
            throw new NotFoundHttpException('Nemáte platný odkaz pro ověření účtu.');
        }

        $error = $authenticationUtils->getLastAuthenticationError();
        if($error)
        {
            $this->addFlash('failure', $this->translator->trans($error->getMessageKey()));
        }

        $form = $formFactory->createNamed('', VerificationFormType::class, null, ['default_email' => $request->query->get('email', '')]);
        $form->add('submit', SubmitType::class, ['label' => 'Ověřit']);

        $this->breadcrumbs->addRoute('verify_email');

        return $this->render('authentication/verification.html.twig', [
            'verificationForm' => $form->createView(),
        ]);
    }
}