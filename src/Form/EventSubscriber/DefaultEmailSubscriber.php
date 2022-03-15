<?php

namespace App\Form\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Security\Core\Security;

/**
 * Subscriber pro nastavení defaultního e-mailu podle přihlášeného uživatele
 *
 * @package App\Form\EventSubscriber
 */
class DefaultEmailSubscriber implements EventSubscriberInterface
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
        $data = $event->getData();
        $data->setEmail($this->defaultEmail);
        $event->setData($data);
    }
}