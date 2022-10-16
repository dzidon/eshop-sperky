<?php

namespace App\Facade;

use DateTime;
use App\Entity\User;
use Psr\Log\LoggerInterface;
use App\Security\EmailVerifier;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

/**
 * Třída řešící registraci nového uživatele
 *
 * @package App\Facade
 */
class UserFacade
{
    private LoggerInterface $logger;
    private EmailVerifier $emailVerifier;
    private ParameterBagInterface $parameterBag;
    private EntityManagerInterface $entityManager;

    public function __construct(LoggerInterface $logger, ParameterBagInterface $parameterBag, EmailVerifier $emailVerifier, EntityManagerInterface $entityManager)
    {
        $this->logger = $logger;
        $this->parameterBag = $parameterBag;
        $this->emailVerifier = $emailVerifier;
        $this->entityManager = $entityManager;
    }

    /**
     * Provede registraci nového uživatele. Pošle potvrzovací e-mail. Pokud už je uživatel zaregistrovaný, vytvoří
     * se fake potvrzovací e-mail, který se neodešle. Persistne uživatele a může flushnout.
     *
     * @param User $user
     * @param bool $flush
     * @return UserFacade
     */
    public function registerUser(User $user, bool $flush): self
    {
        $newUser = $user;
        $existingUser = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $newUser->getEmail()]);
        $registeredUser = null;

        // new user
        if ($existingUser === null)
        {
            $registeredUser = $newUser;
            $newUser->setVerifyLinkLastSent(new DateTime('now'));
            $this->logger->info(sprintf("User %s (ID: %s) has registered a new account using email & password.", $newUser->getUserIdentifier(), $newUser->getId()));
        }
        // existing user
        else if (!$existingUser->isVerified() && $existingUser->canSendAnotherVerifyLink( (int) $this->parameterBag->get('app_email_verify_throttling_interval') ))
        {
            $registeredUser = $existingUser;
            $now = new DateTime('now');
            $existingUser->setVerifyLinkLastSent($now)
                ->setRegistered($now)
                ->setPassword($newUser->getPassword())
            ;

            $this->logger->info(sprintf("Someone has tried to register with the following e-mail: %s. This e-mail is already assigned to an unverified account ID %s. A new verification link has been sent.", $newUser->getEmail(), $existingUser->getId()));
        }

        // save
        if ($registeredUser !== null)
        {
            $this->entityManager->persist($registeredUser);
            if ($flush)
            {
                $this->entityManager->flush();
            }
        }

        // email
        $this->sendVerificationEmail($registeredUser);

        return $this;
    }

    /**
     * Zkusí poslat e-mail pro dokončení registrace. Pokud dostane null uživatele, vytvoří se fake potvrzovací e-mail,
     * který se neodešle.
     *
     * @see EmailVerifier
     * @param User|null $userForEmailConfirmation
     * @return void
     */
    private function sendVerificationEmail(?User $userForEmailConfirmation): void
    {
        try
        {
            $this->emailVerifier->sendEmailConfirmation($userForEmailConfirmation);
        }
        catch (TransportExceptionInterface $exception)
        {
            // tohle se muze vyhodit, jen kdyz $userForEmailConfirmation neni null
            $this->logger->error(sprintf("User %s has tried to register, but the following error occurred in sendEmailConfirmation: %s", $userForEmailConfirmation->getUserIdentifier(), $exception->getMessage()));
        }
    }
}