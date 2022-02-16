<?php

namespace App\Form\EventSubscriber;

use App\Service\EntityCollectionService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

class EntityCollectionRemovalSubscriber implements EventSubscriberInterface
{
    private array $collectionGetters;

    private EntityCollectionService $entityCollectionService;

    public function __construct(EntityCollectionService $entityCollectionService)
    {
        $this->entityCollectionService = $entityCollectionService;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            FormEvents::POST_SET_DATA => 'postSetData',
            FormEvents::POST_SUBMIT => 'postSubmit',
        ];
    }

    public function setCollectionGetters(array $collectionGetters): self
    {
        $this->collectionGetters = $collectionGetters;

        return $this;
    }

    public function postSetData(FormEvent $event): void
    {
        $instance = $event->getData();
        if (!$instance)
        {
            return;
        }

        foreach ($this->collectionGetters as $getElements)
        {
            $this->entityCollectionService->loadCollections([
                ['type' => 'old', 'name' => $getElements, 'collection' => $instance->$getElements()]
            ]);
        }
    }

    public function postSubmit(FormEvent $event): void
    {
        $instance = $event->getData();
        if (!$instance)
        {
            return;
        }

        $form = $event->getForm();
        if($form->isSubmitted() && $form->isValid())
        {
            foreach ($this->collectionGetters as $getElements)
            {
                $this->entityCollectionService->loadCollections([
                    ['type' => 'new', 'name' => $getElements, 'collection' => $instance->$getElements()]
                ]);
            }

            $this->entityCollectionService->removeElementsMissingFromNewCollections();
        }
    }
}