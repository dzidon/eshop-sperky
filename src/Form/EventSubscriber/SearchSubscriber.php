<?php

namespace App\Form\EventSubscriber;

use App\Entity\Detached\Search\Abstraction\SearchModelInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

/**
 * Subscriber pro vyhledávací formuláře.
 *
 * @package App\Form\EventSubscriber
 */
class SearchSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            FormEvents::POST_SUBMIT => 'postSubmit',
        ];
    }

    public function postSubmit(FormEvent $event): void
    {
        $form = $event->getForm();
        if ($form->isSubmitted() && !$form->isValid())
        {
            /** @var SearchModelInterface $searchData */
            $searchData = $form->getData();

            if ($searchData instanceof SearchModelInterface)
            {
                $searchData->invalidateSearch();
            }
        }
    }
}