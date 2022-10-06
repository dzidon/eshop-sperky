<?php

namespace App\Messenger;

use Doctrine\Common\Collections\ArrayCollection;

/**
 * Třída držící elementy kolekcí nějaké entity. Jde použít pro orphan removal.
 *
 * @package App\Messenger
 */
class EntityCollectionsMessenger
{
    private array $collections = [];

    public function getCollections(): array
    {
        return $this->collections;
    }

    public function addCollectionData(string $collectionAttribute, string $parentAttribute, ArrayCollection $children): self
    {
        $this->collections[$collectionAttribute] = [
            'parent' => $parentAttribute,
            'children' => $children,
        ];

        return $this;
    }
}