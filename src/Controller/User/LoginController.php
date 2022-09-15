<?php

namespace App\Controller\User;

use App\Form\FormType\User\LoginFormType;
use App\Service\BreadcrumbsService;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use KnpU\OAuth2ClientBundle\Client\Provider\FacebookClient;
use KnpU\OAuth2ClientBundle\Client\Provider\GoogleClient;
use LogicException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Contracts\Translation\TranslatorInterface;

class LoginController extends AbstractController
{
    /**
     * @Route("/prihlaseni", name="login")
     */
    public function login(BreadcrumbsService $breadcrumbs, FormFactoryInterface $formFactory, Request $request, AuthenticationUtils $authenticationUtils, TranslatorInterface $translator): Response
    {
        if ($this->isGranted('IS_AUTHENTICATED_FULLY'))
        {
            $this->addFlash('failure', 'Už jste přihlášen. Pro nové přihlášení se nejdříve odhlašte.');
            return $this->redirectToRoute('home');
        }

        $error = $authenticationUtils->getLastAuthenticationError();
        if($error)
        {
            $this->addFlash('failure', $translator->trans($error->getMessageKey()));
        }

        $form = $formFactory->createNamed('', LoginFormType::class, null, ['last_email' => $authenticationUtils->getLastUsername()]);
        $form->add('submit', SubmitType::class, ['label' => 'Přihlásit se']);

        $request->getSession()->remove(Security::LAST_USERNAME);

        $breadcrumbs
            ->addRoute('home')
            ->addRoute('login');

        return $this->render('authentication/login.html.twig', [
            'loginForm' => $form->createView(),
        ]);
    }

    /**
     * @Route("/prihlaseni/{service<facebook|google>}", name="login_social")
     */
    public function loginSocial(ClientRegistry $clientRegistry, $service): RedirectResponse
    {
        if ($this->isGranted('IS_AUTHENTICATED_FULLY'))
        {
            $this->addFlash('failure', 'Už jste přihlášen. Pro nové přihlášení se nejdříve odhlašte.');
            return $this->redirectToRoute('home');
        }

        $serviceData = [
            'facebook' => [
                'scopes' => ['public_profile', 'email']
            ],
            'google' => [
                'scopes' => ['https://www.googleapis.com/auth/userinfo.profile', 'https://www.googleapis.com/auth/userinfo.email']
            ],
        ];

        /** @var FacebookClient|GoogleClient $client */
        $client = $clientRegistry->getClient($service);
        return $client->redirect($serviceData[$service]['scopes']);
    }

    /**
     * @Route("/odhlaseni", name="logout")
     */
    public function logout(): void
    {
        throw new LogicException('Tohle se nikdy nevyhodí, je to tu kvůli firewallu.');
    }
}
