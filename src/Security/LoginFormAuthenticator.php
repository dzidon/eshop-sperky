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
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Http\Authenticator\AbstractLoginFormAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\CsrfTokenBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\RememberMeBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\PassportInterface;
use Symfony\Component\Security\Http\Util\TargetPathTrait;

/**
 * Třída LoginFormAuthenticator řeší autentizaci přes přihlašovací formulář (email a heslo).
 *
 * @package App\Security
 */
class LoginFormAuthenticator extends AbstractLoginFormAuthenticator
{
    use TargetPathTrait;

    public const LOGIN_ROUTE = 'login';

    private UrlGeneratorInterface $urlGenerator;
    private LoggerInterface $logger;
    private EntityManagerInterface $entityManager;
    private Security $security;
    private EmailVerifier $emailVerifier;

    public function __construct(UrlGeneratorInterface $urlGenerator, LoggerInterface $logger, EntityManagerInterface $entityManager, Security $security, EmailVerifier $emailVerifier)
    {
        $this->urlGenerator = $urlGenerator;
        $this->logger = $logger;
        $this->entityManager = $entityManager;
        $this->security = $security;
        $this->emailVerifier = $emailVerifier;
    }

    public function authenticate(Request $request): PassportInterface
    {
        if($this->security->isGranted('IS_AUTHENTICATED_FULLY')) //uživatel už je přihlášen úplně
        {
            throw new AlreadyAuthenticatedException();
        }

        $email = $request->request->get('email', '');
        $password = $request->request->get('password', '');
        $token = $request->request->get('_token');

        $request->getSession()->set(Security::LAST_USERNAME, $email);

        return new Passport(
            new UserBadge($email, function($userIdentifier)
            {
                /** @var User $user */
                $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $userIdentifier]);

                if (!$user || $user->getPassword() === null || !$user->isVerified())
                {
                    throw new BadCredentialsException();
                }

                return $user;
            }),
            new PasswordCredentials($password),
            [
                new CsrfTokenBadge('form_login', $token),
                new RememberMeBadge(),
            ]
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        $user = $token->getUser();

        $request->getSession()->getFlashBag()->add('success', 'Přihlášení e-mailem a heslem proběhlo úspěšně.');
        $this->logger->info(sprintf("User %s (ID: %s) has logged in using email and password.", $user->getUserIdentifier(), $user->getId()));

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
}