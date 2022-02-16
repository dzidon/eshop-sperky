<?php

namespace App\Service;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManagerInterface;
use LogicException;

class EntityCollectionService
{
    private array $collections;

    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    private function loadOldCollection(string $name, Collection $collection): self
    {
        $this->collections[$name]['old'] = new ArrayCollection();
        foreach ($collection as $item)
        {
            $this->collections[$name]['old']->add($item);
        }

        return $this;
    }

    private function loadNewCollection(string $name, Collection $collection): self
    {
        $this->collections[$name]['new'] = $collection;

        return $this;
    }

    public function loadCollections(array $data): self
    {
        foreach ($data as $collectionData)
        {
            if (!isset($collectionData['type']) || !isset($collectionData['name']) || !isset($collectionData['collection']) || !($collectionData['type'] === 'old' || $collectionData['type'] === 'new'))
            {
                throw new LogicException('Do loadCollections vkládáte array, ve kterém něco chybí. Jedna přípustná podoba arraye - [["type" => "old", "name" => string, "collection" => kolekce]]. Druhá přípustná podoba arraye - [["type" => "new", "name" => string, "collection" => kolekce]].');
            }

            if($collectionData['type'] === 'old')
            {
                $this->loadOldCollection($collectionData['name'], $collectionData['collection']);
            }
            else
            {
                $this->loadNewCollection($collectionData['name'], $collectionData['collection']);
            }
        }

        return $this;
    }

    public function removeElementsMissingFromNewCollections(): self
    {
        foreach ($this->collections as $collectionPair)
        {
            if(!isset($collectionPair['old']) || !isset($collectionPair['new']))
            {
                throw new LogicException('V EntityCollectionService::removeElementsMissingFromNewCollection chybí v jednom páru kolekcí buď stará nebo nová kolekce.');
            }

            foreach ($collectionPair['old'] as $element)
            {
                if(!$collectionPair['new']->contains($element))
                {
                    $this->entityManager->remove($element);
                }
            }
        }

        return $this;
    }
}