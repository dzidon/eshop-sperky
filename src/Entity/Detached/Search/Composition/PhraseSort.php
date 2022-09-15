<?php

namespace App\Entity\Detached\Search\Composition;

use App\Entity\Detached\Search\Abstraction\AbstractSearch;
use App\Entity\Detached\Search\Atomic\Phrase;
use App\Entity\Detached\Search\Atomic\Sort;

class PhraseSort extends AbstractSearch
{
    private Phrase $phrase;

    private Sort $sort;

    public function __construct(Phrase $phrase, Sort $sort)
    {
        $this->phrase = $phrase;
        $this->sort = $sort;
    }

    public function getPhrase(): Phrase
    {
        return $this->phrase;
    }

    public function getSort(): Sort
    {
        return $this->sort;
    }

    /**
     * @inheritDoc
     */
    public function invalidateSearch(): void
    {
        parent::invalidateSearch();

        $this->phrase->invalidateSearch();
        $this->sort->invalidateSearch();
    }
}