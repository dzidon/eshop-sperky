<?php

namespace App\Facade;

use LogicException;
use GoPay\Payments;
use App\Entity\Order;
use App\Entity\Payment;
use GoPay\Http\Response;
use App\Entity\PaymentMethod;
use GoPay\Definition\Language;
use App\Exception\PaymentException;
use libphonenumber\PhoneNumberUtil;
use libphonenumber\PhoneNumberFormat;
use GoPay\Definition\Payment\Currency;
use Doctrine\ORM\EntityManagerInterface;
use GoPay\Definition\Payment\PaymentItemType;
use GoPay\Definition\Payment\PaymentInstrument;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Třída manipulující s platbami.
 *
 * @package App\Facade
 */
class PaymentFacade
{
    private Payments $payments;
    private UrlGeneratorInterface $router;
    private PhoneNumberUtil $phoneNumberUtil;
    private EntityManagerInterface $entityManager;
    private OrderFacade $orderFacade;

    public function __construct(Payments $payments, UrlGeneratorInterface $router, PhoneNumberUtil $phoneNumberUtil, EntityManagerInterface $entityManager, OrderFacade $orderFacade)
    {
        $this->router = $router;
        $this->payments = $payments;
        $this->entityManager = $entityManager;
        $this->phoneNumberUtil = $phoneNumberUtil;
        $this->orderFacade = $orderFacade;
    }

    /**
     * Vytvoří novou platbu v platební bráně a k ní vytvoří a vrátí odpovídající instanci App\Entity\Payment. Persistne
     * platbu a může i flushnout.
     *
     * @param Order $order
     * @param bool $flush
     * @return Payment|null
     * @throws PaymentException
     */
    public function createPayment(Order $order, bool $flush): ?Payment
    {
        if ($order->getId() === null)
        {
            throw new LogicException('Metoda createPayment v App\Facade\PaymentFacade dostala objednávku s null ID.');
        }

        $paymentMethod = $order->getPaymentMethod();
        if ($paymentMethod !== null && $paymentMethod->isOnline())
        {
            $response = $this->payments->createPayment($this->getNewPaymentData($order));
            if ($response->hasSucceed())
            {
                $payment = new Payment($response->json['id'], $response->json['state'], $order, $response->json['gw_url']);
                $this->entityManager->persist($payment);
                if ($flush)
                {
                    $this->entityManager->flush();
                }

                return $payment;
            }
            else
            {
                throw new PaymentException($this->getErrorString($response));
            }
        }

        return null;
    }

    /**
     * Aktualizuje stav platby. V určitých případech aktualizuje stav objednávky. Persistne platbu a může flushnout.
     *
     * @param Payment $payment
     * @param string $newState
     * @param bool $flush
     * @return $this
     * @throws PaymentException
     */
    public function updatePaymentState(Payment $payment, string $newState, bool $flush): self
    {
        $oldState = $payment->getState();

        if ($oldState !== $newState)
        {
            if (!$payment->isValidStateChange($newState))
            {
                throw new PaymentException('Tato změna stavu platby není platná.');
            }

            $order = $payment->getOrder();

            // objednávka čeká na zaplacení a její platba přechází ze stavu "platební metoda zvolena" do jiného stavu
            if ($order->getLifecycleChapter() === Order::LIFECYCLE_AWAITING_PAYMENT)
            {
                // zaplacení = změna stavu objednávky na "čeká na odeslání"
                if ($oldState === Payment::STATE_PAYMENT_METHOD_CHOSEN && $newState === Payment::STATE_PAID)
                {
                    $order->setLifecycleChapter(Order::LIFECYCLE_AWAITING_SHIPPING);

                    $this->orderFacade->sendInfoEmail($order);
                    $this->entityManager->persist($order);
                }
                // zrušení/timeout = zrušení objednávky
                else if (($oldState === Payment::STATE_PAYMENT_METHOD_CHOSEN && ($newState === Payment::STATE_CANCELED || $newState === Payment::STATE_TIMEOUTED))
                      || ($oldState === Payment::STATE_CREATED               &&  $newState === Payment::STATE_TIMEOUTED))
                {
                    $order->setCancellationReason('Platba zrušena.');
                    if ($newState === Payment::STATE_TIMEOUTED)
                    {
                        $order->setCancellationReason('Čas na zaplacení vypršel.');
                    }

                    $this->orderFacade->cancelOrder($order, true, false);
                }
            }

            $payment->setState($newState);

            $this->entityManager->persist($payment);
            if ($flush)
            {
                $this->entityManager->flush();
            }
        }

        return $this;
    }

    /**
     * @param Response $response
     * @return string
     */
    public function getErrorString(Response $response): string
    {
        $errors = '';
        if (isset($response->json['errors']))
        {
            foreach ($response->json['errors'] as $error)
            {
                $errors .= sprintf('%s ', $error['message']);
            }
        }

        return $errors;
    }

    /**
     * @param Order $order
     * @return array
     */
    private function getNewPaymentData(Order $order): array
    {
        // platební metoda
        $paymentInstrument = PaymentInstrument::PAYMENT_CARD;
        if ($order->getPaymentMethod()->getType() === PaymentMethod::TYPE_TRANSFER)
        {
            $paymentInstrument = PaymentInstrument::BANK_ACCOUNT;
        }

        // země
        $countryCode = 'CZE';
        if ($order->getAddressBillingCountry() === 'Slovensko')
        {
            $countryCode = 'SVK';
        }

        // produkty
        $items = [];
        foreach ($order->getCartOccurences() as $cartOccurence)
        {
            $items[] = [
                'type'   => PaymentItemType::ITEM,
                'name'   => $cartOccurence->getName(),
                'amount' => ceil($cartOccurence->getPriceWithVat() * 100), // v haléřích
            ];
        }

        return [
            'payer' => [
                'default_payment_instrument'  => $paymentInstrument,
                'allowed_payment_instruments' => [$paymentInstrument],
                'contact' => [
                    'first_name'   => $order->getAddressBillingNameFirst(),
                    'last_name'    => $order->getAddressBillingNameLast(),
                    'email'        => $order->getEmail(),
                    'phone_number' => $this->phoneNumberUtil->format($order->getPhoneNumber(), PhoneNumberFormat::E164),
                    'city'         => $order->getAddressBillingTown(),
                    'street'       => $order->getAddressBillingStreet(),
                    'postal_code'  => $order->getAddressBillingZip(),
                    'country_code' => $countryCode,
                ]
            ],
            'amount'        => ceil($order->getTotalPriceWithVat(true) * 100), // v haléřích
            'currency'      => Currency::CZECH_CROWNS,
            'order_number'  => (string) $order->getId(),
            'lang'          => Language::CZECH,
            'items'         => $items,
            'callback' => [
                'return_url'        => $this->router->generate('payment_return'),
                'notification_url'  => $this->router->generate('payment_notification'),
            ],
        ];
    }
}