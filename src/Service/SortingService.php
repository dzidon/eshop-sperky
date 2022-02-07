<?php

namespace App\Service;

use LogicException;

class SortingService
{
    public const ATTRIBUTE_TAG_ASC = '-ASC';
    public const ATTRIBUTE_TAG_DESC = '-DESC';

    /**
     * Vytvoří data potřebná pro řazení
     *
     * Příklad vráceného pole:
     *      'attribute' => 'email'
     *      'order'     => 'ASC'
     *
     * @param $attribute
     * @param $allSortAttributes
     * @return array
     */
    public function createSortData($attribute, $allSortAttributes): array
    {
        if($attribute === null || array_search($attribute, $allSortAttributes) === false)
        {
            $attribute = $allSortAttributes[array_key_first($allSortAttributes)];
        }

        if(str_ends_with($attribute, self::ATTRIBUTE_TAG_ASC))
        {
            return [
                'attribute' => substr($attribute, 0, -4),
                'order' => 'ASC',
            ];
        }
        else if(str_ends_with($attribute, self::ATTRIBUTE_TAG_DESC))
        {
            return [
                'attribute' => substr($attribute, 0, -5),
                'order' => 'DESC',
            ];
        }

        throw new LogicException('Metoda createSortData v SortingService nedokázala vytvořit array potřebný pro řazení. Nejspíše nedostala atribut končící "-ASC", nebo "-DESC".');
    }
}