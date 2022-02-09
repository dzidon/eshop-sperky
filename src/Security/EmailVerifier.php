<?php

namespace App\Security;

use Exception;
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
    private ParameterBagInterface $parameterBag;

    public function __construct(VerifyEmailHelperInterface $helper, MailerInterface $mailer, ParameterBagInterface $parameterBag)
    {
        $this->verifyEmailHelper = $helper;
        $this->mailer = $mailer;
        $this->parameterBag = $parameterBag;
    }

    /**
     * Odešle odkaz na ověření emailu
     *
     * @param string $verifyEmailRouteName
     * @param null|UserInterface $user
     *
     * @throws TransportExceptionInterface
     * @throws Exception
     */
    public function sendEmailConfirmation(string $verifyEmailRouteName, ?UserInterface $user): void
    {
        $usedId = ($user !== null ? $user->getId() : '1');
        $usedEmail = ($user !== null ? $user->getEmail() : 'fake@email.com');

        $email = new TemplatedEmail();
        $email->from(new Address($this->parameterBag->get('app_email_noreply'), $this->parameterBag->get('app_site_name')))
            ->to($usedEmail)
            ->subject('Aktivace účtu')
            ->htmlTemplate('fragments/emails/_verify_account.html.twig');

        $signatureComponents = $this->verifyEmailHelper->generateSignature(
            $verifyEmailRouteName,
            $usedId,
            $usedEmail
        );

        $context = $email->getContext();
        $context['signedUrl'] = $signatureComponents->getSignedUrl();
        $context['expiresAtMessageKey'] = $signatureComponents->getExpirationMessageKey();
        $context['expiresAtMessageData'] = $signatureComponents->getExpirationMessageData();

        $email->context($context);

        if($user === null)
        {
            throw new Exception("This e-mail is already verified or it's too soon for another verification link.");
        }
        else
        {
            $this->mailer->send($email);
        }
    }

    /**
     * Řeší aktivaci účtu po kliknutí na ověřovací odkaz
     *
     * @param Request $request
     * @param UserInterface $user
     *
     * @throws VerifyEmailExceptionInterface
     */
    public function handleEmailConfirmation(Request $request, UserInterface $user): void
    {
        $this->verifyEmailHelper->validateEmailConfirmation($request->getUri(), $user->getId(), $user->getEmail());
    }
}