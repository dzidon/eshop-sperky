<?php

namespace App\Security;

use App\Exception\EmailAlreadyVerifiedException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Security\Core\User\UserInterface;
use SymfonyCasts\Bundle\VerifyEmail\Exception\VerifyEmailExceptionInterface;
use SymfonyCasts\Bundle\VerifyEmail\VerifyEmailHelperInterface;

/**
 * Třída EmailVerifier řeší potvrzovací emaily uživatele
 *
 * @package App\Security
 */
class EmailVerifier
{
    private VerifyEmailHelperInterface $verifyEmailHelper;
    private MailerInterface $mailer;
    private EntityManagerInterface $entityManager;
    private ParameterBagInterface $parameterBag;

    public function __construct(VerifyEmailHelperInterface $helper, MailerInterface $mailer, EntityManagerInterface $manager, ParameterBagInterface $parameterBag)
    {
        $this->verifyEmailHelper = $helper;
        $this->mailer = $mailer;
        $this->entityManager = $manager;
        $this->parameterBag = $parameterBag;
    }

    /**
     * Odešle odkaz na ověření emailu
     *
     * @param string $verifyEmailRouteName
     * @param UserInterface $user
     * @param bool $newUser
     *
     * @throws TransportExceptionInterface
     * @throws \Exception
     */
    public function sendEmailConfirmation(string $verifyEmailRouteName, UserInterface $user, bool $newUser = false): void
    {
        if(!$user->canSendAnotherVerifyLink($this->parameterBag->get('app_email_verify_link_throttle_limit')))
        {
            throw new \Exception('You can only request one verification link per 10 minutes.');
        }

        $email = new TemplatedEmail();
        $email->from(new Address($this->parameterBag->get('app_email_noreply'), $this->parameterBag->get('app_site_name')))
            ->to($user->getEmail())
            ->subject('Aktivace účtu')
            ->htmlTemplate('registration/confirmation_email.html.twig');

        $signatureComponents = $this->verifyEmailHelper->generateSignature(
            $verifyEmailRouteName,
            $user->getId(),
            $user->getEmail()
        );

        $context = $email->getContext();
        $context['signedUrl'] = $signatureComponents->getSignedUrl();
        $context['expiresAtMessageKey'] = $signatureComponents->getExpirationMessageKey();
        $context['expiresAtMessageData'] = $signatureComponents->getExpirationMessageData();

        $email->context($context);

        $this->mailer->send($email);

        if(!$newUser)
        {
            $user->setVerifyLinkLastSent(new \DateTime('now'));
            $this->entityManager->persist($user);
            $this->entityManager->flush();
        }
    }

    /**
     * Řeší aktivaci účtu po kliknutí na ověřovací odkaz
     *
     * @param Request $request
     * @param UserInterface $user
     *
     * @throws EmailAlreadyVerifiedException
     * @throws VerifyEmailExceptionInterface
     */
    public function handleEmailConfirmation(Request $request, UserInterface $user): void
    {
        if($user->isVerified())
        {
            throw new EmailAlreadyVerifiedException();
        }

        $this->verifyEmailHelper->validateEmailConfirmation($request->getUri(), $user->getId(), $user->getEmail());

        $user->setIsVerified(true);

        $this->entityManager->persist($user);
        $this->entityManager->flush();
    }
}
