<?php

namespace App\CatalogFilter;

use App\Entity\ProductSection;
use App\Messenger\NativeQueryDataMessenger;

/**
 * Vytvoří WHERE klauzuli pro vyhledávání produktů přes produktový filtr.
 *
 * @package App\CatalogFilter
 */
class CatalogProductSearchNativeQueryDataBuilder
{
    private array $placeholders = [];

    private array $clauses = [
        'section_id'    => '{prefix}.section_id = :section_id',
        'search_phrase' => '{prefix}.name LIKE :search_phrase',
        'price_min'     => '{prefix}.price_with_vat >= :price_min',
        'price_max'     => '{prefix}.price_with_vat <= :price_max',
    ];

    private bool $invisible = false;

    private ?string $prefix = null;

    private bool $prependWhere = false;

    public function withInvisible(bool $invisible = true): self
    {
        $this->invisible = $invisible;

        return $this;
    }

    public function withPrefix(?string $prefix): self
    {
        $this->prefix = $prefix;

        return $this;
    }

    public function withWhere(bool $prependWhere = true): self
    {
        $this->prependWhere = $prependWhere;

        return $this;
    }

    public function withSection(?ProductSection $section): self
    {
        if ($section === null)
        {
            $this->placeholders['section_id'] = null;
        }
        else
        {
            $this->placeholders['section_id'] = $section->getId();
        }

        return $this;
    }

    public function withSearchPhrase(?string $phrase): self
    {
        if ($phrase === null)
        {
            unset($this->placeholders['search_phrase']);
        }
        else
        {
            $this->placeholders['search_phrase'] = '%' . $phrase . '%';
        }


        return $this;
    }

    public function withPriceMin(?float $priceMin): self
    {
        if ($priceMin === null)
        {
            unset($this->placeholders['price_min']);
        }
        else
        {
            $this->placeholders['price_min'] = $priceMin;
        }

        return $this;
    }

    public function withPriceMax(?float $priceMax): self
    {
        if ($priceMax === null)
        {
            unset($this->placeholders['price_max']);
        }
        else
        {
            $this->placeholders['price_max'] = $priceMax;
        }

        return $this;
    }

    public function build(): NativeQueryDataMessenger
    {
        $clause = '';
        $placeholders = [];

        foreach ($this->placeholders as $placeholderName => $placeholderValue)
        {
            if (!empty($clause))
            {
                $clause .= ' AND ';
            }

            $placeholders[$placeholderName] = $placeholderValue;
            $clause .= $this->clauses[$placeholderName] . ' ';
        }

        // podminka viditelnosti
        if (!$this->invisible)
        {
            if (!empty($clause))
            {
                $clause .= ' AND ';
            }

            $clause .= '
                {prefix}.is_hidden = false
                AND (NOT ({prefix}.available_since IS NOT NULL AND {prefix}.available_since > CURRENT_TIME()))
                AND (NOT ({prefix}.hide_when_sold_out = true AND {prefix}.inventory <= 0))
            ';
        }

        // prefix
        if ($this->prefix === null)
        {
            $clause = str_replace('{prefix}.', '', $clause);
        }
        else
        {
            $clause = str_replace('{prefix}', $this->prefix, $clause);
        }

        // where
        if ($this->prependWhere && !empty($clause))
        {
            $clause = 'WHERE ' . $clause;
        }

        return new NativeQueryDataMessenger($clause, $placeholders);
    }
}