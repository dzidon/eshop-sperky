<?php

namespace App\Service;

use App\EntityCollectionManagement\EntityCollectionEnvelope;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Třída sloužící pro manipulaci s kolekcemi entit.
 *
 * @package App\Service
 */
class EntityCollectionService
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * Z kolekcí odstraní entity, které mají rodiče nastavené na null. Entita a kolekce jsou
     * specifikované v EntityCollectionEnvelope.
     *
     * @param EntityCollectionEnvelope $envelope
     */
    public function removeOrphans(EntityCollectionEnvelope $envelope): void
    {
        $collections = $envelope->getCollections();
        foreach ($collections as $collection)
        {
            foreach ($collection['children'] as $child)
            {
                $getParent = $collection['getterForParent'];
                if ($child->$getParent() === null)
                {
                    $this->entityManager->remove($child);
                }
            }
        }
    }
}