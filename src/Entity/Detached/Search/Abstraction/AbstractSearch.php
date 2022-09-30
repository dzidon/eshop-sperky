<?php

namespace App\Entity\Detached\Search\Abstraction;

/**
 * Abstraktní třída pro modely sloužící k vyhledávání.
 *
 * @package App\Entity\Detached\Search\Abstraction
 */
abstract class AbstractSearch implements SearchModelInterface
{
    protected string $label;

    protected bool $isSearchValid = true;

    public function setLabel(string $label): self
    {
        $this->label = $label;

        return $this;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    /**
     * @inheritDoc
     */
    public function invalidateSearch(): void
    {
        $this->isSearchValid = false;
    }

    /**
     * @inheritDoc
     */
    public function isSearchValid(): bool
    {
        return $this->isSearchValid;
    }
}