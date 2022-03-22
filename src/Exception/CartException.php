<?php

namespace App\Exception;

use Exception;

/**
 * Výjimka pro chyby související s nákupním košíkem
 *
 * @package App\Exception
 */
class CartException extends Exception
{
    public function __construct($message)
    {
        parent::__construct($message);
    }
}