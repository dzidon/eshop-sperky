<?php

namespace App\Security;

use App\Entity\User;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use SymfonyCasts\Bundle\VerifyEmail\Exception\VerifyEmailExceptionInterface;
use SymfonyCasts\Bundle\VerifyEmail\VerifyEmailHelperInterface;

/**
 * Třída EmailVerifier řeší odesílání e-mailů pro dokončení registrace
 *
 * @package App\Security
 */
class EmailVerifier
{
    const VERIFICATION_ROUTE_NAME = 'verify_email';

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
     * @param null|User $user Pokud je null, jedná se ověřovací e-mail, který se nikam nepošle, protože
     *                        uživatel (útočník?) zkouší zaregistrovat e-mail, který už je ověřený.
     *                        Také je možné, že ještě neuplynul čas uvedený v app_email_verify_throttling_interval
     *                        od posledního odeslaného odkazu.
     *
     * @throws TransportExceptionInterface
     */
    public function sendEmailConfirmation(?User $user): void
    {
        $usedId = ($user !== null ? $user->getId() : '1');
        $usedEmail = ($user !== null ? $user->getEmail() : 'fake@email.com');

        $email = new TemplatedEmail();
        $email->from(new Address($this->parameterBag->get('app_email_noreply'), $this->parameterBag->get('app_site_name')))
            ->to($usedEmail)
            ->subject('Aktivace účtu')
            ->htmlTemplate('fragments/emails/_verify_account.html.twig')
        ;

        $signatureComponents = $this->verifyEmailHelper->generateSignature(
            self::VERIFICATION_ROUTE_NAME,
            $usedId,
            $usedEmail,
            ['email' => $usedEmail]
        );

        $context = $email->getContext();
        $context['signedUrl'] = $signatureComponents->getSignedUrl();
        $context['expiresAtMessageKey'] = $signatureComponents->getExpirationMessageKey();
        $context['expiresAtMessageData'] = $signatureComponents->getExpirationMessageData();

        $email->context($context);

        if ($user !== null)
        {
            $this->mailer->send($email);
        }
    }

    /**
     * Řeší aktivaci účtu
     *
     * @param Request $request
     * @param User $user
     *
     * @throws VerifyEmailExceptionInterface
     */
    public function handleEmailConfirmation(Request $request, User $user): void
    {
        $this->verifyEmailHelper->validateEmailConfirmation($request->getUri(), $user->getId(), $user->getEmail());
    }
}