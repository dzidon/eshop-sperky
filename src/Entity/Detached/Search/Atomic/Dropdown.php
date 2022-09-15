<?php

namespace App\Entity\Detached\Search\Atomic;

use App\Entity\Detached\Search\Abstraction\AbstractSearch;

class Dropdown extends AbstractSearch
{
    private $choice = null;

    private array $choices;

    public function __construct(array $choices = [], ?string $label = 'Volby')
    {
        $this->choices = $choices;
        $this->label = $label;
    }

    public function setChoice($choice): self
    {
        $this->choice = $choice;

        return $this;
    }

    public function getChoice()
    {
        return $this->choice;
    }

    public function setChoices(array $choices): self
    {
        $this->choices = $choices;

        return $this;
    }

    public function getChoices(): array
    {
        return $this->choices;
    }

    /**
     * @inheritDoc
     */
    public function invalidateSearch(): void
    {
        parent::invalidateSearch();

        $this->choice = null;
    }
}