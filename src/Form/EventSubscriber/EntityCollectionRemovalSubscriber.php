<?php

namespace App\Form\EventSubscriber;

use App\Service\EntityCollectionService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Security\Core\Security;

/**
 * Subscriber, který jde použít u kolekcí, kde nejde nastavit orphanRemoval. Umožňuje smazat prvky kolekce, které jsou
 * odpojené od rodiče.
 *
 * @package App\Form\EventSubscriber
 */
class EntityCollectionRemovalSubscriber implements EventSubscriberInterface
{
    private array $collectionGetters;

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

    /**
     * Přidá getter rodiče pro kolekci, ve které chceme mazat odpojené objekty.
     *
     * @param string $collectionGetter
     * @param string|null $requiredPermission
     * @return $this
     */
    public function addCollectionGetter(string $collectionGetter, string $requiredPermission = null): self
    {
        if($requiredPermission === null || $this->security->isGranted($requiredPermission))
        {
            $this->collectionGetters[] = $collectionGetter;
        }

        return $this;
    }

    public function postSetData(FormEvent $event): void
    {
        $instance = $event->getData();
        if ($instance)
        {
            foreach ($this->collectionGetters as $getElements)
            {
                $this->entityCollectionService->loadCollections([
                    ['type' => 'old', 'name' => $getElements, 'collection' => $instance->$getElements()]
                ]);
            }
        }
    }

    public function postSubmit(FormEvent $event): void
    {
        $instance = $event->getData();
        if ($instance)
        {
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
                $event->setData($instance);
            }
        }
    }
}