<?php


namespace App\Security;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use KnpU\OAuth2ClientBundle\Security\Authenticator\OAuth2Authenticator;
use League\OAuth2\Client\Provider\FacebookUser;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\RememberMeBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\PassportInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Symfony\Component\Security\Http\Util\TargetPathTrait;

class FacebookAuthenticator extends OAuth2Authenticator
{
    use TargetPathTrait;

    private ClientRegistry $clientRegistry;
    private EntityManagerInterface $entityManager;
    private RouterInterface $router;
    private LoggerInterface $logger;
    private UrlGeneratorInterface $urlGenerator;

    public function __construct(ClientRegistry $clientRegistry, EntityManagerInterface $entityManager, RouterInterface $router, LoggerInterface $logger, UrlGeneratorInterface $urlGenerator)
    {
        $this->clientRegistry = $clientRegistry;
        $this->entityManager = $entityManager;
        $this->router = $router;
        $this->logger = $logger;
        $this->urlGenerator = $urlGenerator;
    }

    public function supports(Request $request): ?bool
    {
        return $request->attributes->get('_route') === 'oauth_check' && $request->get('service') === 'facebook';
    }

    public function authenticate(Request $request): PassportInterface
    {
        $client = $this->clientRegistry->getClient('facebook');
        $accessToken = $this->fetchAccessToken($client);

        return new SelfValidatingPassport(
            new UserBadge($accessToken->getToken(), function() use ($accessToken, $client)
            {
                /** @var FacebookUser $facebookUser */
                $facebookUser = $client->fetchUserFromToken($accessToken);

                $email = $facebookUser->getEmail();

                // pokud už se v minulosti přihlašoval Facebookem
                $existingUser = $this->entityManager->getRepository(User::class)->findOneBy(['facebookId' => $facebookUser->getId()]);

                if ($existingUser)
                {
                    $this->logger->info(sprintf("User %s (ID: %s) has logged in using Facebook. They have used Facebook to log in before (Facebook ID: %s).", $existingUser->getUserIdentifier(), $existingUser->getId(), $existingUser->getFacebookId()));
                    return $existingUser;
                }

                // v minulosti se nepřihlašoval přes Facebook
                $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $email]);
                if($user) //nějaký e-mail z naší DB se shoduje s emailem daného FB účtu
                {
                    $user->setFacebookId($facebookUser->getId());
                    $user->setIsVerified(true);
                    $this->entityManager->persist($user);
                    $this->entityManager->flush();

                    $this->logger->info(sprintf("User %s (ID: %s) has logged in using Facebook by linking email addresses (Facebook ID: %s).", $user->getUserIdentifier(), $user->getId(), $user->getFacebookId()));
                }
                else //žadný e-mail z naší DB se neshoduje s emailem daného FB účtu
                {
                    $user = new User();
                    $user->setEmail($facebookUser->getEmail());
                    $user->setFacebookId($facebookUser->getId());
                    $user->setIsVerified(true);
                    $this->entityManager->persist($user);
                    $this->entityManager->flush();

                    $this->logger->info(sprintf("User %s (ID: %s) has registered a new account using Facebook (Facebook ID: %s).", $user->getUserIdentifier(), $user->getId(), $user->getFacebookId()));
                }

                return $user;
            }),
            [
                new RememberMeBadge(),
            ]
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        if ($targetPath = $this->getTargetPath($request->getSession(), $firewallName)) {
            return new RedirectResponse($targetPath);
        }

        return new RedirectResponse($this->urlGenerator->generate('home'));
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        $message = strtr($exception->getMessageKey(), $exception->getMessageData());

        return new Response($message, Response::HTTP_FORBIDDEN);
    }
}