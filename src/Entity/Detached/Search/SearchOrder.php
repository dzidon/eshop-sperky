<?php

namespace App\Entity\Detached\Search;

class SearchOrder extends SearchAndSort
{
    private $lifecycle;

    public function __construct(array $allSortData, string $searchHelp = null)
    {
        parent::__construct($allSortData, $searchHelp);
    }

    public function getLifecycle(): ?string
    {
        return $this->lifecycle;
    }

    public function setLifecycle(?string $lifecycle): self
    {
        $this->lifecycle = $lifecycle;

        return $this;
    }

    public function reset(): void
    {
        parent::reset();

        $this->lifecycle = null;
    }
}