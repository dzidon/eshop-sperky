<?php

namespace App\Form\EventSubscriber;

use App\Entity\User;
use LogicException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * Subscriber řešící hashování hesla uživatele.
 *
 * @package App\Form\EventSubscriber
 */
class PasswordHashSubscriber implements EventSubscriberInterface
{
    private UserPasswordHasherInterface $userPasswordHasher;

    public function __construct(UserPasswordHasherInterface $userPasswordHasher)
    {
        $this->userPasswordHasher = $userPasswordHasher;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            FormEvents::POST_SUBMIT => 'postSubmit',
        ];
    }

    public function postSubmit(FormEvent $event): void
    {
        $form = $event->getForm();
        if ($form->isSubmitted() && $form->isValid())
        {
            $user = $event->getData();
            if (!$user instanceof User)
            {
                throw new LogicException(sprintf('%s musí dostat objekt třídy %s.', get_class($this), User::class));
            }

            $hashedPassword = $this->userPasswordHasher->hashPassword(
                $user,
                $user->getPlainPassword(),
            );

            $user
                ->setPassword($hashedPassword)
                ->eraseCredentials();

            $event->setData($user);
        }
    }
}