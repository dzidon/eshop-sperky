<?php

namespace App\Service;

use SoapFault;
use SoapClient;
use LogicException;
use App\Entity\Order;
use libphonenumber\PhoneNumberUtil;
use libphonenumber\PhoneNumberFormat;
use App\Exception\PacketaException;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

/**
 * Třída řešící komunikaci s API Zásilkovny.
 *
 * @package App\Service
 */
class PacketaApiService
{
    private Order $order;
    private string $secret;
    private string $eshopName;
    private SoapClient $client;

    private PhoneNumberUtil $phoneNumberUtil;

    public function __construct(PhoneNumberUtil $phoneNumberUtil, ParameterBagInterface $parameterBag)
    {
        $this->phoneNumberUtil = $phoneNumberUtil;

        $this->eshopName = (string) $parameterBag->get('app_site_name');
        $this->secret = (string) $parameterBag->get('app_packeta_secret');
        $this->client = new SoapClient($parameterBag->get('app_packeta_api_url'));
    }

    /**
     * Vrátí data o zásilce, nebo null pokud nastala chyba.
     *
     * @param Order $order
     * @return object|null
     * @throws PacketaException
     */
    public function packetStatus(Order $order): ?object
    {
        $this->setOrder($order);
        $data = null;

        try
        {
            $data = $this->client->packetStatus($this->secret, (string) $this->order->getId());
        }
        catch (SoapFault $exception)
        {
            throw new PacketaException([$exception->getMessage()]);
        }

        return $data;
    }

    /**
     * Pokusí se vytvořit zásilku. Po úspěšném vytvoření vrátí data o vytvořené zásilce, jinak null.
     *
     * @param Order $order
     * @return object|null
     * @throws PacketaException
     */
    public function createPacket(Order $order): ?object
    {
        $this->setOrder($order);
        $data = null;

        try
        {
            $data = $this->client->createPacket($this->secret, $this->getPacketAttributes());
        }
        catch (SoapFault $exception)
        {
            throw new PacketaException( $this->getPacketAttributesFaults($exception->detail->PacketAttributesFault) );
        }

        return $data;
    }

    /**
     * @param Order $order
     */
    private function setOrder(Order $order): void
    {
        $this->order = $order;

        // objednávka musí mít ID
        if ($this->order->getId() === null)
        {
            throw new LogicException(sprintf('Služba App\Service\PacketaApiService dostala do metody initialize objednávku s null ID.'));
        }
    }

    /**
     * Vezme chyby z obdrženého PacketAttributesFault a vloží je do pole errors.
     *
     * @param $PacketAttributesFault
     * @return array
     */
    private function getPacketAttributesFaults($PacketAttributesFault): array
    {
        $faults = [];

        $faultStructure = $PacketAttributesFault->attributes->fault;
        if (is_array($faultStructure))
        {
            foreach ($faultStructure as $fault)
            {
                $faults[] = $fault->fault;
            }
        }
        else
        {
            $faults[] = $faultStructure->fault;
        }

        return $faults;
    }

    /**
     * Převede objednávku na array PacketAttributes.
     *
     * @return array
     */
    private function getPacketAttributes(): array
    {
        $phoneNumberWithSpaces = $this->phoneNumberUtil->format($this->order->getPhoneNumber(), PhoneNumberFormat::INTERNATIONAL);
        $phoneNumber = preg_replace('/\s+/', '', $phoneNumberWithSpaces);

        $attributes = [
            'number'    => (string) $this->order->getId(),
            'name'      => $this->order->getAddressDeliveryNameFirst(),
            'surname'   => $this->order->getAddressDeliveryNameLast(),
            'email'     => $this->order->getEmail(),
            'phone'     => $phoneNumber,
            'addressId' => (int) $this->order->getAddressDeliveryAdditionalInfo(),
            'cod'       => $this->order->getCashOnDelivery(),
            'value'     => $this->order->getTotalPriceWithVat($withMethods = false),
            'weight'    => $this->order->getWeight(),
            'eshop'     => $this->eshopName,
            'currency'  => 'CZK',
        ];

        $company = $this->order->getAddressBillingCompany();
        if ($company !== null)
        {
            $attributes['company'] = $company;
        }

        return $attributes;
    }
}