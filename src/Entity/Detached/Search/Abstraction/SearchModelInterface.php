<?php

namespace App\Entity\Detached\Search\Abstraction;

/**
 * Rozhraní řešící stav validace modelu.
 *
 * @package App\Entity\Detached\Search\Abstraction
 */
interface SearchModelInterface
{
    /**
     * Volá se v případě, že uživatel zadá nevalidní vstup (např. zadá neexistující atribut řazení). Vyresetuje atributy,
     * které obsahují uživatelský vstup, aby se nevalidní hledání neprovedlo.
     */
    public function invalidateSearch(): void;

    /**
     * Vrátí true, pokud je hledání validní.
     *
     * @return bool
     */
    public function isSearchValid(): bool;
}