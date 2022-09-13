<?php

namespace App\Service;

use App\Entity\EntityOrphanRemovalInterface;
use App\Messenger\EntityCollectionsMessenger;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\PersistentCollection;
use LogicException;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

/**
 * Třída sloužící pro manipulaci s kolekcemi entit.
 *
 * @package App\Service
 */
class EntityCollectionService
{
    private EntityManagerInterface $entityManager;
    private PropertyAccessorInterface $propertyAccessor;

    public function __construct(EntityManagerInterface $entityManager, PropertyAccessorInterface $propertyAccessor)
    {
        $this->entityManager = $entityManager;
        $this->propertyAccessor = $propertyAccessor;
    }

    /**
     * Vytvoří messenger obsahující obsah kolekcí pro orphan removal.
     *
     * @param EntityOrphanRemovalInterface $entity
     * @return EntityCollectionsMessenger
     */
    public function createEntityCollectionsMessengerForOrphanRemoval(EntityOrphanRemovalInterface $entity): EntityCollectionsMessenger
    {
        $messenger = new EntityCollectionsMessenger();

        foreach ($entity->getOrphanRemovalCollectionAttributes() as $collectionAttributes)
        {
            if (!isset($collectionAttributes['collection']) || !isset($collectionAttributes['parent']) || !is_string($collectionAttributes['collection']) || !is_string($collectionAttributes['parent']))
            {
                throw new LogicException('Entita má metodu getOrphanRemovalCollectionAttributes ve špatném tvaru.');
            }

            $collection = $this->propertyAccessor->getValue($entity, $collectionAttributes['collection']);
            if ($collection instanceof PersistentCollection && !$collection->isInitialized()) // Kolekce není načtená, nemá smysl dělat orphan removal
            {
                continue;
            }

            $children = new ArrayCollection();
            foreach ($collection as $element)
            {
                $children[] = $element;
            }

            $messenger->addCollectionData($collectionAttributes['collection'], $collectionAttributes['parent'], $children);
        }

        return $messenger;
    }

    /**
     * Z kolekcí odstraní entity, které mají rodiče nastavené na null. Entita a kolekce jsou
     * specifikované v EntityCollectionsMessenger.
     *
     * @param EntityCollectionsMessenger $messenger
     */
    public function removeOrphans(EntityCollectionsMessenger $messenger): void
    {
        $collections = $messenger->getCollections();
        foreach ($collections as $collection)
        {
            foreach ($collection['children'] as $child)
            {
                $parent = $this->propertyAccessor->getValue($child, $collection['parent']);
                if ($parent === null)
                {
                    $this->entityManager->remove($child);
                }
            }
        }
    }
}