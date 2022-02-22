<?php

namespace App\Service;

use App\Entity\Detached\ContactEmail;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;

/**
 * Třída řešící odesílání e-mailů zákaznické podpoře přímo z webu
 *
 * @package App\Service
 */
class ContactEmailService
{
    private string $senderEmail;
    private string $subject;
    private string $text;
    private Email $email;

    private MailerInterface $mailer;
    private ParameterBagInterface $parameterBag;

    public function __construct(MailerInterface $mailer, ParameterBagInterface $parameterBag)
    {
        $this->mailer = $mailer;
        $this->parameterBag = $parameterBag;
    }

    public function getSenderEmail(): string
    {
        return $this->senderEmail;
    }

    public function getSubject(): string
    {
        return $this->subject;
    }

    public function getText(): string
    {
        return $this->text;
    }

    /**
     * Připraví e-mail pro odeslání
     *
     * @param ContactEmail $emailData
     * @return $this
     */
    public function initialize(ContactEmail $emailData): self
    {
        $recipientEmail = (string) $this->parameterBag->get('app_site_email');
        $this->senderEmail = (string) $emailData->getEmail();
        $this->subject = (string) $emailData->getSubject();
        $this->text = (string) $emailData->getText();

        $this->email = new Email();
        $this->email->from(new Address($this->senderEmail))
            ->to($recipientEmail)
            ->subject($this->subject)
            ->text($this->text);

        return $this;
    }

    /**
     * Pošle e-mail
     *
     * @return $this
     * @throws TransportExceptionInterface
     */
    public function send(): self
    {
        $this->mailer->send($this->email);

        return $this;
    }
}