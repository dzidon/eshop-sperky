<?php

namespace App\Security;

use App\Entity\User;
use App\Exception\AlreadyAuthenticatedException;
use App\Exception\InsufficientSocialDataException;
use Doctrine\ORM\EntityManagerInterface;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use KnpU\OAuth2ClientBundle\Client\Provider\FacebookClient;
use KnpU\OAuth2ClientBundle\Client\Provider\GoogleClient;
use KnpU\OAuth2ClientBundle\Security\Authenticator\OAuth2Authenticator;
use League\OAuth2\Client\Provider\FacebookUser;
use League\OAuth2\Client\Provider\GoogleUser;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\RememberMeBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\PassportInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Symfony\Component\Security\Http\Util\TargetPathTrait;
use Symfony\Contracts\Translation\TranslatorInterface;

class SocialAuthenticator extends OAuth2Authenticator
{
    use TargetPathTrait;

    private ClientRegistry $clientRegistry;
    private EntityManagerInterface $entityManager;
    private RouterInterface $router;
    private LoggerInterface $logger;
    private UrlGeneratorInterface $urlGenerator;
    private TranslatorInterface $translator;
    private Security $security;

    private string $requestedService;
    private array $serviceData;

    public function __construct(ClientRegistry $clientRegistry, EntityManagerInterface $entityManager, RouterInterface $router, LoggerInterface $logger, UrlGeneratorInterface $urlGenerator, TranslatorInterface $translator, Security $security)
    {
        $this->clientRegistry = $clientRegistry;
        $this->entityManager = $entityManager;
        $this->router = $router;
        $this->logger = $logger;
        $this->urlGenerator = $urlGenerator;
        $this->translator = $translator;
        $this->security = $security;

        $this->serviceData = [
            'facebook' => [
                'name' => 'Facebook',
                'userIdAttribute' => 'facebookId',
                'userIdAttributeSetter' => 'setFacebookId',
            ],
            'google' => [
                'name' => 'Google',
                'userIdAttribute' => 'googleId',
                'userIdAttributeSetter' => 'setGoogleId',
            ],
        ];
    }

    public function supports(Request $request): ?bool
    {
        $this->requestedService = $request->get('service', ''); //facebook/google
        return $request->attributes->get('_route') === 'oauth_check' && isset($this->serviceData[$this->requestedService]);
    }

    public function authenticate(Request $request): PassportInterface
    {
        if($this->security->isGranted('IS_AUTHENTICATED_FULLY')) //uživatel už je přihlášen úplně
        {
            throw new AlreadyAuthenticatedException();
        }

        $serviceName = $this->serviceData[$this->requestedService]['name'];
        $serviceIdAttribute = $this->serviceData[$this->requestedService]['userIdAttribute'];
        $serviceIdAttributeSetter = $this->serviceData[$this->requestedService]['userIdAttributeSetter'];

        /** @var FacebookClient|GoogleClient $client */
        $client = $this->clientRegistry->getClient($this->requestedService);
        $accessToken = $this->fetchAccessToken($client);

        return new SelfValidatingPassport(
            new UserBadge($accessToken->getToken(), function() use ($accessToken, $client, $serviceName, $serviceIdAttribute, $serviceIdAttributeSetter)
            {
                /** @var FacebookUser|GoogleUser $socialUser */
                $socialUser = $client->fetchUserFromToken($accessToken);

                $socialEmail = $socialUser->getEmail();
                $socialId = $socialUser->getId();

                if($socialEmail === null || $socialId === null)
                {
                    $this->logger->error(sprintf("Failed %s login due to insufficient data provided (Social email: %s, Social ID: %s).", $serviceName, $socialEmail, $socialId));
                    throw new InsufficientSocialDataException();
                }

                // pokud už se v minulosti přihlašoval danou službou
                $existingUser = $this->entityManager->getRepository(User::class)->findOneBy([$serviceIdAttribute => $socialId]); //např. facebookId => 651519191561
                if ($existingUser) //social id nalezeno
                {
                    if($socialEmail === $existingUser->getUserIdentifier()) //v db exisutje user s danym emailem a social id
                    {
                        $this->logger->info(sprintf("User %s (ID: %s) has logged in using %s. They have used this service to log in before (Social ID: %s).", $existingUser->getUserIdentifier(), $existingUser->getId(), $serviceName, $socialId));
                        return $existingUser;
                    }
                    else //v db exisutje user s danym social id, email vsak nesedi, takze social id odpojime
                    {
                        $existingUser->$serviceIdAttributeSetter(null);
                        $this->entityManager->persist($existingUser);
                        //kod pokracuje a dalsi postup se resi dole
                    }
                }

                // v minulosti se nepřihlašoval přes danou službu
                $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $socialEmail]);
                if($user) //nějaký e-mail z naší DB se shoduje s emailem daného social účtu
                {
                    $this->logger->info(sprintf("User %s (ID: %s) has logged in using %s by linking email addresses (Social ID: %s).", $user->getUserIdentifier(), $user->getId(), $serviceName, $socialId));
                }
                else //žadný e-mail z naší DB se neshoduje s emailem daného social účtu
                {
                    $user = new User();
                    $user->setEmail($socialEmail);

                    $this->logger->info(sprintf("User %s has registered a new account using %s (Social ID: %s).", $user->getUserIdentifier(), $serviceName, $socialId));
                }

                $user->$serviceIdAttributeSetter($socialId);
                $user->setIsVerified(true);
                $this->entityManager->persist($user);
                $this->entityManager->flush();

                return $user;
            }),
            [
                new RememberMeBadge(),
            ]
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        $request->getSession()->getFlashBag()->add('success', sprintf('Přihlášení přes %s proběhlo úspěšně.', $this->serviceData[$this->requestedService]['name']));

        if ($targetPath = $this->getTargetPath($request->getSession(), $firewallName))
        {
            return new RedirectResponse($targetPath);
        }

        return new RedirectResponse($this->urlGenerator->generate('home'));
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        $message = strtr($exception->getMessageKey(), $exception->getMessageData());

        $request->getSession()->getFlashBag()->add('failure', $this->translator->trans($message));

        return new RedirectResponse($this->urlGenerator->generate('home'));
    }
}