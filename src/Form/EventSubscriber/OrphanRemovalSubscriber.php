<?php

namespace App\Form\EventSubscriber;

use App\EntityManagement\EntityCollectionEnvelope;
use App\Service\EntityCollectionService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Security\Core\Security;

/**
 * Subscriber, který v požadovaných kolekcích provede orphan removal. Je používán v kombinaci s CollectionType.
 *
 * @package App\Form\EventSubscriber
 */
class OrphanRemovalSubscriber implements EventSubscriberInterface
{
    private array $collectionGetters = [];
    private EntityCollectionEnvelope $envelope;

    private Security $security;
    private EntityCollectionService $entityCollectionService;

    public function __construct(Security $security, EntityCollectionService $entityCollectionService)
    {
        $this->security = $security;
        $this->entityCollectionService = $entityCollectionService;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            FormEvents::POST_SET_DATA => 'postSetData',
            FormEvents::POST_SUBMIT => 'postSubmit',
        ];
    }

    public function setCollectionGetters(array $collectionGetters): void
    {
        $this->collectionGetters = $collectionGetters;
    }

    public function postSetData(FormEvent $event): void
    {
        $instance = $event->getData();
        $this->envelope = new EntityCollectionEnvelope($instance, $this->collectionGetters);
    }

    public function postSubmit(): void
    {
        $this->entityCollectionService->removeOrphans($this->envelope);
    }
}