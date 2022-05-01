<?php

namespace App\Form\EventSubscriber;

use App\Entity\User;
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
        /** @var User|null $user */
        $user = $this->security->getUser();
        if ($user !== null)
        {
            $data = $event->getData();
            if ($data->getEmail() === null)
            {
                $data->setEmail($user->getEmail());
                $event->setData($data);
            }
        }
    }
}