<?php

namespace App\Service;

use DateTime;
use App\Entity\Interfaces\UpdatableEntityInterface;
use Doctrine\ORM\EntityManagerInterface;

class EntityUpdatingService
{
    private UpdatableEntityInterface $mainInstance;
    private array $collectionGetters;
    private DateTime $now;

    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
        $this->now = new DateTime('now');
    }

    public function setMainInstance(UpdatableEntityInterface $mainInstance): self
    {
        $this->mainInstance = $mainInstance;

        return $this;
    }

    public function setCollectionGetters(array $collectionGetters): self
    {
        $this->collectionGetters = $collectionGetters;

        return $this;
    }

    private function instancePersistOrSetUpdated(UpdatableEntityInterface $instance): self
    {
        if ($instance->getId() === null)
        {
            $this->entityManager->persist($instance);
        }
        else
        {
            $instance->setUpdated($this->now);
        }

        return $this;
    }

    public function mainInstancePersistOrSetUpdated(): self
    {
        $this->instancePersistOrSetUpdated($this->mainInstance);

        return $this;
    }

    public function collectionItemsSetUpdated(): self
    {
        foreach ($this->collectionGetters as $collectionGetter)
        {
            foreach ($this->mainInstance->$collectionGetter() as $item)
            {
                $this->instancePersistOrSetUpdated($item);
            }
        }

        return $this;
    }
}