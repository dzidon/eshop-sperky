<?php

namespace App\Request\Transformer;

/**
 * Abstraktní třída pomáhající při převodu HTTP požadavku.
 *
 * @package App\Request\Transformer
 */
class AbstractRequestTransformer
{
    /**
     * Zajistí, aby zadaná hodnota byla buď int nebo null.
     *
     * @param $value
     * @return int|null
     */
    protected function valueAsIntOrNull($value): ?int
    {
        if (is_int($value))
        {
            return $value;
        }

        if (is_string($value) && $value !== '')
        {
            return (int) $value;
        }

        return null;
    }

    /**
     * Zajistí, aby zadaná hodnota byla buď array nebo null.
     *
     * @param $value
     * @return array|null
     */
    protected function valueAsArrayOrNull($value): ?array
    {
        if (!is_array($value))
        {
            return null;
        }

        return $value;
    }

    /**
     * Zajistí, aby zadaná hodnota byla array.
     *
     * @param $value
     * @return array|null
     */
    protected function valueAsArray($value): ?array
    {
        if (!is_array($value))
        {
            return [];
        }

        return $value;
    }
}