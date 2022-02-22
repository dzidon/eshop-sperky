<?php

namespace App\Form\EventSubscriber;

use App\Entity\Detached\ContactEmail;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Security\Core\Security;

/**
 * Subscriber pro kontaktní formulář
 *
 * @package App\Form\EventSubscriber
 */
class ContactSubscriber implements EventSubscriberInterface
{
    private string $defaultEmail = '';

    public function __construct(Security $security)
    {
        $user = $security->getUser();
        if($user)
        {
            $this->defaultEmail = $user->getEmail();
        }
    }

    public static function getSubscribedEvents(): array
    {
        return [
            FormEvents::PRE_SET_DATA => 'preSetData',
        ];
    }

    public function preSetData(FormEvent $event): void
    {
        /** @var ContactEmail $emailData */
        $emailData = $event->getData();
        $emailData->setEmail($this->defaultEmail);
        $event->setData($emailData);
    }
}