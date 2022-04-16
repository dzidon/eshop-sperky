<?php

namespace App\Service;

use App\Entity\Order;
use LogicException;
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
     * Připraví e-mail pro odeslání
     *
     * @param Order $order
     * @return $this
     */
    public function initialize(Order $order): self
    {
        $this->order = $order;
        $senderEmail = $this->parameterBag->get('app_email_noreply');
        $senderName = $this->parameterBag->get('app_site_name');
        $recipientEmail = (string) $this->order->getEmail();

        $this->email = new TemplatedEmail();
        $this->email
            ->from(new Address($senderEmail, $senderName))
            ->to(new Address($recipientEmail))
        ;

        return $this;
    }

    /**
     * Pošle e-mail podle aktuálního stavu objednávky
     *
     * @return $this
     * @throws TransportExceptionInterface
     */
    public function send(): self
    {
        if ($this->order->getLifecycleChapter() < Order::LIFECYCLE_AWAITING_PAYMENT)
        {
            throw new LogicException('App\Service\OrderEmailService nemůže poslat potvrzovací e-mail pro objednávku, která má lifecycleChapter menší než App\Entity\Order::LIFECYCLE_AWAITING_PAYMENT.');
        }
        else if ($this->order->getLifecycleChapter() === Order::LIFECYCLE_AWAITING_PAYMENT)
        {
            $this->addAwaitingPaymentContent();
        }
        else if ($this->order->getLifecycleChapter() > Order::LIFECYCLE_AWAITING_PAYMENT)
        {
            $this->addPaidContent();
        }

        $this->addOrderDataToContext();
        $this->mailer->send($this->email);

        return $this;
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
     * Zajistí, aby Twig šablona měla přístup k objednávce
     */
    private function addOrderDataToContext(): void
    {
        $context = $this->email->getContext();
        $context['orderId'] = $this->order->getId();
        $context['orderToken'] = $this->order->getToken();
        $this->email->context($context);
    }
}