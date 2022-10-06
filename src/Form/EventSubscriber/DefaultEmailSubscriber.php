<?php

namespace App\Form\EventSubscriber;

use App\Entity\EntityEmailInterface;
use App\Entity\User;
use LogicException;
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
        $user = $event->getData();
        if (!$user instanceof EntityEmailInterface)
        {
            throw new LogicException(sprintf('%s musí dostat objekt třídy, která implementuje %s.', get_class($this), EntityEmailInterface::class));
        }

        /** @var User|null $loggedInUser */
        $loggedInUser = $this->security->getUser();

        if ($loggedInUser !== null)
        {
            if ($user->getEmail() === null)
            {
                $user->setEmail($loggedInUser->getEmail());
                $event->setData($user);
            }
        }
    }
}