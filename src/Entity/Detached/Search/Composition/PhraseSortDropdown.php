<?php

namespace App\Entity\Detached\Search\Composition;

use App\Entity\Detached\Search\Abstraction\AbstractSearch;
use App\Entity\Detached\Search\Atomic\Dropdown;

class PhraseSortDropdown extends AbstractSearch
{
    private PhraseSort $phraseSort;

    private Dropdown $dropdown;

    public function __construct(PhraseSort $phraseSort, Dropdown $dropdown)
    {
        $this->phraseSort = $phraseSort;
        $this->dropdown = $dropdown;
    }

    public function getPhraseSort(): PhraseSort
    {
        return $this->phraseSort;
    }

    public function getDropdown(): Dropdown
    {
        return $this->dropdown;
    }

    /**
     * @inheritDoc
     */
    public function invalidateSearch(): void
    {
        parent::invalidateSearch();

        $this->phraseSort->invalidateSearch();
        $this->dropdown->invalidateSearch();
    }
}