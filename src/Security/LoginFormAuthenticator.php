<?php

namespace App\Security;

use App\Entity\User;
use App\Exception\AlreadyAuthenticatedException;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Http\Authenticator\AbstractLoginFormAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\CsrfTokenBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\RememberMeBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\PassportInterface;
use Symfony\Component\Security\Http\Util\TargetPathTrait;

class LoginFormAuthenticator extends AbstractLoginFormAuthenticator
{
    use TargetPathTrait;

    public const LOGIN_ROUTE = 'login';

    private UrlGeneratorInterface $urlGenerator;
    private LoggerInterface $logger;
    private EntityManagerInterface $entityManager;
    private Security $security;

    private bool $justRegistered = false;

    public function __construct(UrlGeneratorInterface $urlGenerator, LoggerInterface $logger, EntityManagerInterface $entityManager, Security $security)
    {
        $this->urlGenerator = $urlGenerator;
        $this->logger = $logger;
        $this->entityManager = $entityManager;
        $this->security = $security;
    }

    public function authenticate(Request $request): PassportInterface
    {
        if($this->security->isGranted('IS_AUTHENTICATED_FULLY')) //uživatel už je přihlášen úplně
        {
            throw new AlreadyAuthenticatedException();
        }

        $email = $request->request->get('email', '');
        $password = $request->request->get('password', '');
        $token = $request->request->get('_csrf_token');

        $request->getSession()->set(Security::LAST_USERNAME, $email);

        return new Passport(
            new UserBadge($email, function($userIdentifier)
            {
                $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $userIdentifier]);

                if (!$user) //uzivatel nenalezen, nema cenu pokracovat
                {
                    throw new UserNotFoundException();
                }

                if($user->getPassword() === null) //pokud uzivatel nema nastavene heslo, nema cenu pokracovat
                {
                    throw new BadCredentialsException();
                }

                return $user;
            }),
            new PasswordCredentials($password),
            [
                new CsrfTokenBadge('authenticate', $token),
                new RememberMeBadge(),
            ]
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        $user = $token->getUser();

        if($this->justRegistered)
        {
            $request->getSession()->getFlashBag()->add('success', 'Byli jste úspěšně zaregistrováni! Svůj účet aktivujete kliknutím na odkaz, který vám byl odeslán na email.');
            $this->logger->info(sprintf("User %s (ID: %s) has registered using email and password.", $user->getUserIdentifier(), $user->getId()));
        }
        else
        {
            $request->getSession()->getFlashBag()->add('success', 'Přihlášení heslem proběhlo úspěšně.');
            $this->logger->info(sprintf("User %s (ID: %s) has logged in using email and password.", $user->getUserIdentifier(), $user->getId()));
        }

        if ($targetPath = $this->getTargetPath($request->getSession(), $firewallName))
        {
            return new RedirectResponse($targetPath);
        }

        return new RedirectResponse($this->urlGenerator->generate('home'));
    }

    protected function getLoginUrl(Request $request): string
    {
        return $this->urlGenerator->generate(self::LOGIN_ROUTE);
    }

    public function setJustRegistered(bool $justRegistered): self
    {
        $this->justRegistered = $justRegistered;

        return $this;
    }
}
