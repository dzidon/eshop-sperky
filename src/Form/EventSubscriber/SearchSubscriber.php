<?php

namespace App\Form\EventSubscriber;

use App\Entity\Detached\Search\Abstraction\SearchModelInterface;
use LogicException;
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
            $searchData = $event->getData();
            if (!$searchData instanceof SearchModelInterface)
            {
                throw new LogicException(sprintf('%s musí dostat objekt třídy, která implementuje %s.', get_class($this), SearchModelInterface::class));
            }

            $searchData->invalidateSearch();
        }
    }
}