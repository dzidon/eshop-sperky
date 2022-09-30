<?php

namespace App\Entity\Detached\Search\Atomic;

use App\Entity\Detached\Search\Abstraction\AbstractSearch;

class Phrase extends AbstractSearch
{
    protected ?string $text = null;

    protected ?string $searchHelp = null;

    public function __construct(string $searchHelp = null, string $label = 'Hledat')
    {
        $this->searchHelp = $searchHelp;
        $this->label = $label;
    }

    public function getText(): ?string
    {
        return $this->text;
    }

    public function setText(?string $text): self
    {
        $this->text = $text;

        return $this;
    }

    public function getSearchHelp(): ?string
    {
        return $this->searchHelp;
    }

    public function setSearchHelp(?string $searchHelp): self
    {
        $this->searchHelp = $searchHelp;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function invalidateSearch(): void
    {
        parent::invalidateSearch();

        $this->text = null;
    }
}