<?php

namespace App\Entity\Detached\Search;

use LogicException;

class SearchAndSort
{
    public const ATTRIBUTE_TAG_ASC = '-ASC';
    public const ATTRIBUTE_TAG_DESC = '-DESC';

    protected $searchPhrase;
    protected $searchHelp;
    protected $sortBy;
    protected array $allSortData;

    public function __construct(array $allSortData, string $searchHelp = null)
    {
        $this->allSortData = $allSortData;
        $this->searchHelp = $searchHelp;
    }

    public function getSearchPhrase(): ?string
    {
        return $this->searchPhrase;
    }

    public function setSearchPhrase(?string $searchPhrase): self
    {
        $this->searchPhrase = $searchPhrase;

        return $this;
    }

    public function getSearchHelp(): string
    {
        return $this->searchHelp;
    }

    public function setSearchHelp(string $searchHelp): self
    {
        $this->searchHelp = $searchHelp;

        return $this;
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

    /**
     * Vytvoří data potřebná pro řazení v DQL
     *
     * Příklad vráceného pole:
     *      ['attribute' => 'email',
     *       'order'     => 'ASC'],
     * Další příklad:
     *      ['attribute' => 'price',
     *       'order'     => 'DESC'],
     *
     * @return array
     */
    public function getDqlSortData(): array
    {
        if($this->sortBy === null || array_search($this->sortBy, $this->allSortData) === false)
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

        throw new LogicException('Metoda getDqlSortData v App\Entity\Detached\Search\SearchAndSort nedokázala vytvořit array potřebný pro řazení. Nejspíše nedostala atribut končící "-ASC", nebo "-DESC".');
    }

    public function reset(): void
    {
        $this->searchPhrase = null;
        $this->sortBy = null;
    }
}
