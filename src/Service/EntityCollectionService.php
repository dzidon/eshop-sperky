<?php

namespace App\Service;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManagerInterface;
use LogicException;

/**
 * Třída umožňující sledovat rozdíly v kolekci entity například před odesláním formuláře a po odeslání formuláře.
 * Se změnou v kolekci jde následně nějak naložit, například je možné odstranit chybějící entity z databáze.
 * Dá se použít v kombinaci s CollectionType.
 *
 * @package App\Service
 */
class EntityCollectionService
{
    /**
     * Obsahuje stará data kolekcí a nová data kolekcí
     *
     * @var array
     */
    private array $collections = [];

    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * Načte stará data dané kolekce
     *
     * @param string $name Nějaký název této kolekce, který je uložen v klíči arraye collections
     * @param Collection $collection
     */
    private function loadOldCollection(string $name, Collection $collection): void
    {
        $this->collections[$name]['old'] = new ArrayCollection();
        foreach ($collection as $item)
        {
            $this->collections[$name]['old']->add($item);
        }
    }

    /**
     * Načte nová data dané kolekce
     *
     * @param string $name Nějaký název této kolekce, který je uložen v klíči arraye collections
     * @param Collection $collection
     */
    private function loadNewCollection(string $name, Collection $collection): void
    {
        $this->collections[$name]['new'] = $collection;
    }

    /**
     * Načte stará nebo nová data jedné nebo více kolekcí.
     *
     * @param array $data Načítání starých dat -> ['type' => 'old', 'name' => 'jmeno...', 'collection' => $collection]
     *                    Načítání nových dat  -> ['type' => 'new', 'name' => 'jmeno...', 'collection' => $collection]
     *
     *                    $collection musí být typu Doctrine\Common\Collections\Collection
     * @return $this
     */
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

    /**
     * Smaže entity, které chybí v nových kolekcích.
     */
    public function removeElementsMissingFromNewCollections(): void
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

        $this->collections = [];
    }
}