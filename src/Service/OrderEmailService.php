<?php

namespace App\Service;

use App\Entity\Order;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Mime\Address;

/**
 * Třída řešící odesílání e-mailů po dokončení objednávky
 *
 * @package App\Service
 */
class OrderEmailService
{
    private Order $order;
    private TemplatedEmail $email;

    private MailerInterface $mailer;
    private ParameterBagInterface $parameterBag;

    public function __construct(MailerInterface $mailer, ParameterBagInterface $parameterBag)
    {
        $this->mailer = $mailer;
        $this->parameterBag = $parameterBag;
    }

    /**
     * Pošle e-mail tvůrci objednávky podle aktuálního stavu objednávky
     *
     * @param Order $order
     * @throws TransportExceptionInterface
     */
    public function send(Order $order): void
    {
        $this->order = $order;
        if ($this->order->getLifecycleChapter() >= Order::LIFECYCLE_AWAITING_PAYMENT)
        {
            $this->createEmail();

            if ($this->order->getLifecycleChapter() === Order::LIFECYCLE_AWAITING_PAYMENT)
            {
                $this->addAwaitingPaymentContent();
            }
            else if ($this->order->getLifecycleChapter() === Order::LIFECYCLE_CANCELLED)
            {
                $this->addCancelledContent();
            }
            else
            {
                $this->addPaidContent();
            }

            $this->addOrderDataToContext();
            $this->mailer->send($this->email);
        }
    }

    /**
     * Vytvoří minimální podobu e-mailu pro odeslání
     */
    private function createEmail(): void
    {
        $senderEmail = $this->parameterBag->get('app_email_noreply');
        $senderName = $this->parameterBag->get('app_site_name');
        $recipientEmail = (string) $this->order->getEmail();

        $this->email = new TemplatedEmail();
        $this->email
            ->from(new Address($senderEmail, $senderName))
            ->to(new Address($recipientEmail))
        ;
    }

    /**
     * Přidá do e-mailu předmět a obsah pro pouze vytvořenou (nezaplacenou) objednávku
     */
    private function addAwaitingPaymentContent(): void
    {
        $this->email
            ->subject(sprintf('Vytvořena objednávka č. %s', $this->order->getId()))
            ->htmlTemplate('fragments/emails/_order_created.html.twig')
        ;
    }

    /**
     * Přidá do e-mailu předmět a obsah pro už zaplacenou objednávku
     */
    private function addPaidContent(): void
    {
        $this->email
            ->subject(sprintf('Přijata objednávka č. %s', $this->order->getId()))
            ->htmlTemplate('fragments/emails/_order_accepted.html.twig')
        ;
    }

    /**
     * Přidá do e-mailu předmět a obsah pro zrušenou objednávku
     */
    private function addCancelledContent(): void
    {
        $this->email
            ->subject(sprintf('Zrušena objednávka č. %s', $this->order->getId()))
            ->htmlTemplate('fragments/emails/_order_cancelled.html.twig')
        ;
    }

    /**
     * Předá Twig šabloně data potřebná pro vykreslení
     */
    private function addOrderDataToContext(): void
    {
        $context = $this->email->getContext();
        $context['orderId'] = $this->order->getId();
        $context['orderToken'] = $this->order->getToken();
        $context['reason'] = $this->order->getCancellationReason();
        $this->email->context($context);
    }
}