<?php

namespace App\EntityCollectionManagement;

use Doctrine\Common\Collections\ArrayCollection;
use LogicException;

/**
 * Třída držící elementy kolekcí nějaké entity. Jde použít pro orphan removal.
 *
 * @package App\EntityCollectionManagement
 */
class EntityCollectionEnvelope
{
    private array $collections;

    public function __construct(object $entity, $collections)
    {
        foreach ($collections as $collection)
        {
            if (!isset($collection['getterForCollection']) || !isset($collection['getterForParent']) || !is_string($collection['getterForCollection']) || !is_string($collection['getterForParent']))
            {
                throw new LogicException('Parametr collections v konstruktoru třídy EntityCollectionEnvelope dostal data ve špatném tvaru.');
            }

            $this->collections[$collection['getterForCollection']] = [
                'getterForParent' => $collection['getterForParent'],
                'children' => new ArrayCollection(),
            ];

            $getChildren = $collection['getterForCollection'];
            foreach ($entity->$getChildren() as $element)
            {
                $this->collections[$collection['getterForCollection']]['children'][] = $element;
            }
        }
    }

    /**
     * @return array
     */
    public function getCollections(): array
    {
        return $this->collections;
    }
}