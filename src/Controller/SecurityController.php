<?php

namespace App\Controller;

use App\Security\LoginFormAuthenticator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class SecurityController extends AbstractController
{
    /**
     * @Route("/login", name="login")
     */
    public function login(Request $request, AuthenticationUtils $authenticationUtils): Response
    {
        if ($this->getUser()) {
             return $this->redirectToRoute('home');
        }

        // ziska login error, pokud nejaky existuje
        $error = $authenticationUtils->getLastAuthenticationError();

        // posledni username (email) zadany uzivatelem
        $lastUsername = $authenticationUtils->getLastUsername();

        // stav "remember me" checkboxu
        $rememberMeChecked = $request->getSession()->get(LoginFormAuthenticator::LAST_REMEMBER_ME, '');

        $request->getSession()->remove(Security::LAST_USERNAME);
        $request->getSession()->remove(LoginFormAuthenticator::LAST_REMEMBER_ME);

        return $this->render('security/login.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error,
            'remember_me_checked' => $rememberMeChecked,
        ]);
    }

    /**
     * @Route("/logout", name="logout")
     */
    public function logout(): void
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }
}
