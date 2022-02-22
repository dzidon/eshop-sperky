<?php

namespace App\Form\EventSubscriber;

use App\Entity\User;
use App\Form\Type\AgreePrivacyType;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Security\Core\Security;

/**
 * Subscriber řešící přidání checkboxu pro souhlas se zpracováním osobních údajů jen v případě,
 * že se editovaný uživatel rovná editujícímu uživateli.
 *
 * @package App\Form\EventSubscriber
 */
class AgreePrivacySubscriber implements EventSubscriberInterface
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
        /** @var User $editedUser */
        $editedUser = $event->getData();
        $editingUser = $this->security->getUser();
        if ($editedUser && $editedUser === $editingUser)
        {
            $event->getForm()->add('agreePrivacy', AgreePrivacyType::class);
        }
    }
}