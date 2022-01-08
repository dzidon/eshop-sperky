<?php

namespace App\Form\EventSubscriber;

use App\Form\Type\AgreePrivacyType;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Security\Core\Security;

/**
 * Subscriber obsahující logiku k zobrazení checkboxu se zpracováním osobních údajů. Pokud uživatel edituje
 * osobní údaje jiného uživatele, nebudeme mu zobrazovat checkbox.
 *
 * @package App\Form\EventSubscriber
 */
class AddPrivacyFieldSubscriber implements EventSubscriberInterface
{
    private Security $security;

    public function __construct(Security $security)
    {
        $this->security = $security;
    }

    public static function getSubscribedEvents(): array
    {
        return [FormEvents::PRE_SET_DATA => 'preSetData'];
    }

    public function preSetData(FormEvent $event): void
    {
        $userAuthenticated = $this->security->getUser();
        $userInForm = $event->getData();
        $form = $event->getForm();

        if ($userAuthenticated === $userInForm)
        {
            $form->add('agreePrivacy', AgreePrivacyType::class, [
                'label' => 'Souhlasím se zpracováním osobních údajů',
            ]);
        }
    }
}