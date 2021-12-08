<?php

namespace App\Controller;

use App\Service\BreadcrumbsService;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use KnpU\OAuth2ClientBundle\Client\Provider\FacebookClient;
use KnpU\OAuth2ClientBundle\Client\Provider\GoogleClient;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class LoginController extends AbstractController
{
    private BreadcrumbsService $breadcrumbs;

    public function __construct(BreadcrumbsService $breadcrumbs)
    {
        $this->breadcrumbs = $breadcrumbs;
    }

    /**
     * @Route("/prihlaseni", name="login")
     */
    public function login(Request $request, AuthenticationUtils $authenticationUtils): Response
    {
        if ($this->isGranted('IS_AUTHENTICATED_FULLY'))
        {
            $this->addFlash('failure', 'Už jste přihlášen. Pro nové přihlášení se nejdříve odhlašte.');
            return $this->redirectToRoute('home');
        }

        // ziska login error, pokud nejaky existuje
        $error = $authenticationUtils->getLastAuthenticationError();

        // posledni username (email) zadany uzivatelem
        $lastUsername = $authenticationUtils->getLastUsername();

        $request->getSession()->remove(Security::LAST_USERNAME);

        return $this->render('security/login.html.twig', [
            'lastUsername' => $lastUsername,
            'error' => $error,
            'breadcrumbs' => $this->breadcrumbs->addRoute('home')->addRoute('login'),
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
                'scopes' => ['https://www.googleapis.com/auth/userinfo.email', 'https://www.googleapis.com/auth/userinfo.profile']
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
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }
}