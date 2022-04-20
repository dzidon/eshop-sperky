<?php

namespace App\Service;

use libphonenumber\PhoneNumberFormat;
use SoapFault;
use SoapClient;
use LogicException;
use App\Entity\Order;
use libphonenumber\PhoneNumberUtil;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

/**
 * Třída řešící komunikaci s API Zásilkovny.
 *
 * @package App\Service
 */
class PacketaApiService
{
    private Order $order;
    private array $errors = [];
    private bool $hasErrors = false;

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
     * @param Order $order
     * @return $this
     */
    public function setOrder(Order $order): self
    {
        $this->order = $order;

        // objednávka musí mít ID
        if ($this->order->getId() === null)
        {
            throw new LogicException('App\Service\PacketaApiService nesmí dostat do metody setOrder objednávku s null ID.');
        }
        $this->errors = [];
        $this->hasErrors = false;

        return $this;
    }

    /**
     * Vrátí data o zásilce, nebo null pokud nastala chyba.
     *
     * @return object|null
     */
    public function packetStatus(): ?object
    {
        $data = null;

        try
        {
            $data = $this->client->packetStatus($this->secret, $this->order->getId());
        }
        catch (SoapFault $exception)
        {
            $this->addError($exception->getMessage());
        }

        return $data;
    }

    /**
     * Pokusí se vytvořit zásilku. Po úspěšném vytvoření vrátí data o vytvořené zásilce, jinak null.
     *
     * @return object|null
     */
    public function createPacket(): ?object
    {
        $data = null;

        try
        {
            $data = $this->client->createPacket($this->secret, $this->getPacketAttributes());
        }
        catch (SoapFault $exception)
        {
            $this->addPacketAttributesFaultToErrors($exception->detail->PacketAttributesFault);
        }

        return $data;
    }

    /**
     * @return array
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * @return bool
     */
    public function hasErrors(): bool
    {
        return $this->hasErrors;
    }

    /**
     * @param string $error
     */
    private function addError(string $error): void
    {
        $this->hasErrors = true;
        $this->errors[] = $error;
    }

    /**
     * Vezme chyby z obdrženého PacketAttributesFault a vloží je do pole errors.
     *
     * @param $PacketAttributesFault
     */
    private function addPacketAttributesFaultToErrors($PacketAttributesFault): void
    {
        $faultStructure = $PacketAttributesFault->attributes->fault;
        if (is_array($faultStructure))
        {
            foreach ($faultStructure as $fault)
            {
                $this->addError($fault->fault);
            }
        }
        else
        {
            $this->addError($faultStructure->fault);
        }
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