<?php

namespace App\Form\EventSubscriber;

use App\Entity\Address;
use App\Entity\Order;
use App\Entity\User;
use LogicException;
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
    private Security $security;

    public function __construct(Security $security)
    {
        $this->security = $security;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            FormEvents::PRE_SET_DATA => 'preSetData',
        ];
    }

    public function preSetData(FormEvent $event): void
    {
        $order = $event->getData();
        if (!$order instanceof Order)
        {
            throw new LogicException(sprintf('%s musí dostat objekt třídy %s.', get_class($this), Order::class));
        }

        $this->setDefaultData($event);
        $this->addDeliveryAddressFields($event);
    }

    private function setDefaultData(FormEvent $event): void
    {
        /** @var User $user */
        $user = $this->security->getUser();
        if($user !== null)
        {
            /** @var Order $order */
            $order = $event->getData();

            if($order->getPhoneNumber() === null)
            {
                $order->setPhoneNumber($user->getPhoneNumber());
            }

            if($order->getAddressDeliveryNameFirst() === null)
            {
                $order->setAddressDeliveryNameFirst($user->getNameFirst());
            }

            if($order->getAddressDeliveryNameLast() === null)
            {
                $order->setAddressDeliveryNameLast($user->getNameLast());
            }

            if($order->getEmail() === null)
            {
                $order->setEmail($user->getEmail());
            }
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
                    'attr' => [
                        'class' => 'addressDeliveryCountry',
                    ],
                    'label' => 'Země',
                ])
                ->add('addressDeliveryStreet', TextType::class, [
                    'attr' => [
                        'class' => 'addressDeliveryStreet',
                    ],
                    'label' => 'Ulice a číslo popisné',
                ])
                ->add('addressDeliveryAdditionalInfo', TextType::class, [
                    'required' => false,
                    'attr' => [
                        'class' => 'addressDeliveryAdditionalInfo',
                    ],
                    'label' => 'Doplněk adresy',
                ])
                ->add('addressDeliveryTown', TextType::class, [
                    'attr' => [
                        'class' => 'addressDeliveryTown',
                    ],
                    'label' => 'Obec',
                ])
                ->add('addressDeliveryZip', TextType::class, [
                    'attr' => [
                        'class' => 'addressDeliveryZip',
                    ],
                    'label' => 'PSČ',
                ])
            ;
        }
    }
}