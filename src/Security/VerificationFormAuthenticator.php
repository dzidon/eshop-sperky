<?php

namespace App\Security;

use App\Entity\User;
use App\Exception\AlreadyAuthenticatedException;
use App\Exception\VerifyLinkInvalidException;
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
use SymfonyCasts\Bundle\VerifyEmail\Exception\VerifyEmailExceptionInterface;

/**
 * Třída VerificationFormAuthenticator řeší autentizaci přes ověřovací formulář (email, heslo, signature z odkazu).
 *
 * @package App\Security
 */
class VerificationFormAuthenticator extends AbstractLoginFormAuthenticator
{
    public const VERIFICATION_ROUTE = 'verify_email';

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

    public function supports(Request $request): bool
    {
        return $request->isMethod('POST') && $request->attributes->get('_route') === self::VERIFICATION_ROUTE;
    }

    public function authenticate(Request $request): PassportInterface
    {
        if($this->security->isGranted('IS_AUTHENTICATED_REMEMBERED')) //uživatel už je přihlášen
        {
            throw new AlreadyAuthenticatedException();
        }

        $email = $request->request->get('email', '');
        $password = $request->request->get('password', '');
        $token = $request->request->get('_token');

        return new Passport(
            new UserBadge($email, function($userIdentifier) use ($request)
            {
                /** @var User $user */
                $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $userIdentifier]);

                if(!$user || $user->getPassword() === null || $user->isVerified())
                {
                    throw new BadCredentialsException();
                }

                try
                {
                    $this->emailVerifier->handleEmailConfirmation($request, $user);
                }
                catch (VerifyEmailExceptionInterface $exception)
                {
                    throw new VerifyLinkInvalidException();
                }

                return $user;
            }),
            new PasswordCredentials($password),
            [
                new CsrfTokenBadge('form_verification', $token),
                new RememberMeBadge(),
            ]
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        $user = $token->getUser();
        $user->setIsVerified(true);
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $request->getSession()->getFlashBag()->add('success', 'Ověření e-mailu proběhlo úspěšně, byli jste přihlášeni!');
        $this->logger->info(sprintf("User %s (ID: %s) has verified their email and logged in.", $user->getUserIdentifier(), $user->getId()));

        return new RedirectResponse($this->urlGenerator->generate('home'));
    }

    protected function getLoginUrl(Request $request): string
    {
        return $this->urlGenerator->generate(self::VERIFICATION_ROUTE, [
            'email' => $request->query->get('email', ''),
            'expires' => $request->query->get('expires', ''),
            'signature' => $request->query->get('signature', ''),
            'token' => $request->query->get('token', ''),
        ]);
    }
}
