<?php

namespace App\Form\EventSubscriber;

use App\Entity\Address;
use App\Entity\Order;
use App\Entity\User;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Security\Core\Security;

/**
 * Subscriber pro nastavení defaultních dat do objednávky podle přihlášeného uživatele
 *
 * @package App\Form\EventSubscriber
 */
class OrderAddressesSubscriber implements EventSubscriberInterface
{
    private $defaultPhoneNumber = null;
    private $defaultNameFirst = null;
    private $defaultNameLast = null;
    private $defaultEmail = null;

    public function __construct(Security $security)
    {
        /** @var User $user */
        $user = $security->getUser();
        if($user !== null)
        {
            $this->defaultPhoneNumber = $user->getPhoneNumber();
            $this->defaultNameFirst = $user->getNameFirst();
            $this->defaultNameLast = $user->getNameLast();
            $this->defaultEmail = $user->getEmail();
        }
    }

    public static function getSubscribedEvents(): array
    {
        return [
            FormEvents::PRE_SET_DATA => 'preSetData',
            FormEvents::SUBMIT => 'submit',
        ];
    }

    public function preSetData(FormEvent $event): void
    {
        $this->setDefaultData($event);
        $this->addDeliveryAddressFields($event);
    }

    public function submit(FormEvent $event): void
    {
        /** @var Order $order */
        $order = $event->getData();

        if (!$order->isCompanyChecked())
        {
            $order->resetDataCompany();
        }

        if (!$order->isBillingAddressChecked())
        {
            $order->resetAddressBilling();
        }

        if (!$order->isNoteChecked())
        {
            $order->setNote(null);
        }
    }

    private function setDefaultData(FormEvent $event): void
    {
        /** @var Order $order */
        $order = $event->getData();

        if($order->getPhoneNumber() === null)
        {
            $order->setPhoneNumber($this->defaultPhoneNumber);
        }

        if($order->getAddressDeliveryNameFirst() === null)
        {
            $order->setAddressDeliveryNameFirst($this->defaultNameFirst);
        }

        if($order->getAddressDeliveryNameLast() === null)
        {
            $order->setAddressDeliveryNameLast($this->defaultNameLast);
        }

        if($order->getEmail() === null)
        {
            $order->setEmail($this->defaultEmail);
        }
    }

    private function addDeliveryAddressFields(FormEvent $event): void
    {
        /** @var Order $order */
        $order = $event->getData();

        if(!$order->isAddressDeliveryLocked())
        {
            $form = $event->getForm();

            $form
                ->add('addressDeliveryCountry', ChoiceType::class, [
                    'choices' => Address::COUNTRY_NAMES_DROPDOWN,
                    'label' => 'Země',
                ])
                ->add('addressDeliveryStreet', TextType::class, [
                    'label' => 'Ulice a číslo popisné',
                ])
                ->add('addressDeliveryAdditionalInfo', TextType::class, [
                    'required' => false,
                    'label' => 'Doplněk adresy',
                ])
                ->add('addressDeliveryTown', TextType::class, [
                    'label' => 'Obec',
                ])
                ->add('addressDeliveryZip', TextType::class, [
                    'label' => 'PSČ',
                ])
            ;
        }
    }
}