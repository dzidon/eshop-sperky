<?php

namespace App\Service;

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
 * @package App\Service
 */
class UserRegistrationService
{
    /**
     * Nový objekt uživatele, kterého chceme registrovat
     *
     * @var User
     */
    private User $newUser;

    /**
     * Již existující uživatel
     *
     * @var User|null
     */
    private $existingUser;

    /**
     * Uživatel, kterému bude poslán e-mail pro dokončení registrace
     *
     * @var User|null
     */
    private $userForEmailConfirmation;

    /**
     * Datum a čas registrace
     *
     * @var DateTime
     */
    private DateTime $registrationDateTime;

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
     * Provede registraci nového uživatele
     *
     * @param User $user
     */
    public function register(User $user): void
    {
        $this->newUser = $user;
        $this->existingUser = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $this->newUser->getEmail()]);
        $this->registrationDateTime = new DateTime('now');

        if ($this->existingUser === null)
        {
            $this->saveNewUser();
        }
        else if (!$this->existingUser->isVerified() && $this->existingUser->canSendAnotherVerifyLink( (int) $this->parameterBag->get('app_email_verify_throttling_interval') ))
        {
            $this->saveExistingUser();
        }

        $this->sendVerificationEmail();
    }

    /**
     * Uloží nového uživatele do DB
     */
    private function saveNewUser(): void
    {
        $this->newUser->setVerifyLinkLastSent($this->registrationDateTime);
        $this->entityManager->persist($this->newUser);
        $this->entityManager->flush();

        $this->userForEmailConfirmation = $this->newUser;
        $this->logger->info(sprintf("User %s (ID: %s) has registered a new account using email & password.", $this->newUser->getUserIdentifier(), $this->newUser->getId()));
    }

    /**
     * Uloží existujícího uživatele do DB
     */
    private function saveExistingUser(): void
    {
        $this->existingUser->setVerifyLinkLastSent($this->registrationDateTime);
        $this->existingUser->setRegistered($this->registrationDateTime);
        $this->existingUser->setPassword($this->newUser->getPassword());

        $this->entityManager->persist($this->existingUser);
        $this->entityManager->flush();

        $this->userForEmailConfirmation = $this->existingUser;
        $this->logger->info(sprintf("Someone has tried to register with the following e-mail: %s. This e-mail is already assigned to an unverified account ID %s. A new verification link has been sent.", $this->newUser->getEmail(), $this->existingUser->getId()));
    }

    /**
     * Zkusí poslat e-mail pro dokončení registrace
     *
     * @see EmailVerifier
     */
    private function sendVerificationEmail(): void
    {
        try
        {
            $this->emailVerifier->sendEmailConfirmation($this->userForEmailConfirmation);
        }
        catch (TransportExceptionInterface $exception)
        {
            $this->logger->error(sprintf("User %s has tried to register, but the following error occurred in sendEmailConfirmation: %s", $this->newUser->getUserIdentifier(), $exception->getMessage()));
        }

        // uživatel už je ověřený nebo je moc brzo na další ověřovací odkaz
        if ($this->userForEmailConfirmation === null)
        {
            $this->logger->error(sprintf("User %s has tried to register, but this email is already verified or it's too soon for another verification link.", $this->newUser->getUserIdentifier()));
        }
    }
}