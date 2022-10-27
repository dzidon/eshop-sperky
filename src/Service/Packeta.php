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
class Packeta
{
    private ?SoapClient $client = null;
    private string $secret;
    private string $eshopName;

    private PhoneNumberUtil $phoneNumberUtil;
    private ParameterBagInterface $parameterBag;

    public function __construct(PhoneNumberUtil $phoneNumberUtil, ParameterBagInterface $parameterBag)
    {
        $this->phoneNumberUtil = $phoneNumberUtil;
        $this->parameterBag = $parameterBag;

        $this->eshopName = (string) $this->parameterBag->get('app_site_name');
        $this->secret = (string) $this->parameterBag->get('app_packeta_secret');
    }

    /**
     * Vrátí true, pokud v systému Zásilkovny existuje zásilka pro danou objednávku.
     *
     * @param Order $order
     * @return bool
     */
    public function packetExists(Order $order): bool
    {
        try
        {
            $this->packetStatus($order);
            return true;
        }
        catch (PacketaException $exception)
        {
            return false;
        }
    }

    /**
     * Vrátí data o zásilce.
     *
     * @param Order $order
     * @return object
     * @throws PacketaException
     */
    public function packetStatus(Order $order): object
    {
        $this->initialize($order);

        try
        {
            return $this->client->packetStatus($this->secret, (string) $order->getId());
        }
        catch (SoapFault $exception)
        {
            throw new PacketaException([$exception->getMessage()]);
        }
    }

    /**
     * Pokusí se vytvořit zásilku. Po úspěšném vytvoření vrátí data o vytvořené zásilce.
     *
     * @param Order $order
     * @return object
     * @throws PacketaException
     */
    public function createPacket(Order $order): object
    {
        $this->initialize($order);

        try
        {
            return $this->client->createPacket($this->secret, $this->getPacketAttributes($order));
        }
        catch (SoapFault $exception)
        {
            $errors = [$exception->getMessage()];
            if (isset($exception->detail))
            {
                $errors = $this->getPacketAttributesFaults($exception->detail->PacketAttributesFault);
            }

            throw new PacketaException($errors);
        }
    }

    /**
     * @param Order $order
     * @throws PacketaException
     */
    private function initialize(Order $order): void
    {
        // objednávka musí mít ID
        if ($order->getId() === null)
        {
            throw new LogicException('Služba App\Service\Packeta dostala do metody initialize objednávku s null ID.');
        }

        // připojení
        if ($this->client === null)
        {
            try
            {
                $this->client = new SoapClient($this->parameterBag->get('app_packeta_api_url'), [
                    'cache_wsdl' => WSDL_CACHE_MEMORY
                ]);
            }
            catch (SoapFault $exception)
            {
                throw new PacketaException(['Nepodařilo se připojit do systému Zásilkovny, zkuste to prosím znovu.']);
            }
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
     * @param Order $order
     * @return array
     */
    private function getPacketAttributes(Order $order): array
    {
        $attributes = [
            'number'    => (string) $order->getId(),
            'name'      => $order->getAddressDeliveryNameFirst(),
            'surname'   => $order->getAddressDeliveryNameLast(),
            'email'     => $order->getEmail(),
            'phone'     => $this->phoneNumberUtil->format($order->getPhoneNumber(), PhoneNumberFormat::E164),
            'addressId' => (int) $order->getAddressDeliveryAdditionalInfo(),
            'cod'       => $order->getCashOnDelivery(),
            'value'     => $order->getTotalPriceWithVat($withMethods = false),
            'weight'    => $order->getWeight(),
            'eshop'     => $this->eshopName,
            'currency'  => 'CZK',
        ];

        $company = $order->getAddressBillingCompany();
        if ($company !== null)
        {
            $attributes['company'] = $company;
        }

        return $attributes;
    }
}