<?php

namespace App\Security;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;
use Symfony\Component\Security\Core\Security;

class AuthenticationEntryPoint implements AuthenticationEntryPointInterface
{
    private UrlGeneratorInterface $urlGenerator;
    private Security $security;

    public function __construct(UrlGeneratorInterface $urlGenerator, Security $security)
    {
        $this->urlGenerator = $urlGenerator;
        $this->security = $security;
    }

    public function start(Request $request, AuthenticationException $authException = null): RedirectResponse
    {
        if($this->security->isGranted('IS_AUTHENTICATED_REMEMBERED'))
        {
            $request->getSession()->getFlashBag()->add('warning', 'Před provedením této akce musíte z bezpečnostních důvodů opakovat přihlášení.');
        }
        else
        {
            $request->getSession()->getFlashBag()->add('warning', 'Před provedením této akce se musíte přihlásit.');
        }

        return new RedirectResponse($this->urlGenerator->generate('login'));
    }
}