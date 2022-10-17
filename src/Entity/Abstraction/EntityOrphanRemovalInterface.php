<?php

namespace App\Entity\Abstraction;

/**
 * Interface pro entity, které mají mít orphan removal v kolekcích.
 *
 * @package App\Entity\Abstraction
 */
interface EntityOrphanRemovalInterface
{
    /**
     * Vrací pole s atributy kolekcí, na které se má vztahovat orphan removal.
     *
     * Tvar:
     * [
     *    ['collection' => 'a', 'parent' => 'b']
     *    ['collection' => 'x', 'parent' => 'y']
     * ]
     *
     * @return array
     */
    public static function getOrphanRemovalCollectionAttributes(): array;
}