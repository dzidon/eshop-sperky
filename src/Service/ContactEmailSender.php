<?php

namespace App\Service;

use App\Entity\Detached\ContactEmail;
use Psr\Log\LoggerInterface;
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
class ContactEmailSender
{
    private MailerInterface $mailer;
    private LoggerInterface $logger;
    private ParameterBagInterface $parameterBag;

    public function __construct(MailerInterface $mailer, LoggerInterface $logger, ParameterBagInterface $parameterBag)
    {
        $this->mailer = $mailer;
        $this->logger = $logger;
        $this->parameterBag = $parameterBag;
    }

    /**
     * Sestaví a pošle e-mail zákaznické podpoře
     *
     * @param ContactEmail $emailData
     * @throws TransportExceptionInterface
     */
    public function send(ContactEmail $emailData): void
    {
        $recipientEmail = (string) $this->parameterBag->get('app_site_email');
        $senderEmail = (string) $emailData->getEmail();
        $subject = (string) $emailData->getSubject();
        $text = (string) $emailData->getText();

        $email = new Email();
        $email->from(new Address($senderEmail))
            ->to($recipientEmail)
            ->subject($subject)
            ->text($text)
        ;

        $this->mailer->send($email);
        $this->logger->info(sprintf("Someone has sent a contact email with a subject '%s' as %s.", $subject, $senderEmail));
    }
}