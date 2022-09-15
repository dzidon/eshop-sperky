<?php

namespace App\Entity\Detached\Search\Atomic;

use App\Entity\Detached\Search\Abstraction\AbstractSearch;

class Sort extends AbstractSearch
{
    public const ATTRIBUTE_TAG_ASC = '-ASC';
    public const ATTRIBUTE_TAG_DESC = '-DESC';

    protected ?string $sortBy = null;

    protected array $allSortData;

    public function __construct(array $allSortData, ?string $label = 'Řazení')
    {
        $this->allSortData = $allSortData;
        $this->label = $label;
    }

    public function getSortBy(): ?string
    {
        return $this->sortBy;
    }

    public function setSortBy(?string $sortBy): self
    {
        $this->sortBy = $sortBy;

        return $this;
    }

    public function getAllSortData(): array
    {
        return $this->allSortData;
    }

    public function setAllSortData(array $allSortData): self
    {
        $this->allSortData = $allSortData;

        return $this;
    }

    public function getDqlSortData(): array
    {
        if($this->sortBy === null || in_array($this->sortBy, $this->allSortData) === false)
        {
            $this->sortBy = ($this->allSortData[array_key_first($this->allSortData)]);
        }

        if(str_ends_with($this->sortBy, self::ATTRIBUTE_TAG_ASC))
        {
            return [
                'attribute' => substr($this->sortBy, 0, -4),
                'order' => 'ASC',
            ];
        }
        else if(str_ends_with($this->sortBy, self::ATTRIBUTE_TAG_DESC))
        {
            return [
                'attribute' => substr($this->sortBy, 0, -5),
                'order' => 'DESC',
            ];
        }

        throw new \LogicException('Metoda getDqlSortData v App\Search\Model nedokázala vytvořit array potřebný pro řazení. Nejspíše nedostala atribut končící "-ASC", nebo "-DESC".');
    }

    /**
     * @inheritDoc
     */
    public function invalidateSearch(): void
    {
        parent::invalidateSearch();

        $this->sortBy = null;
    }
}