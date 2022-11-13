<?php

namespace App\Exception;

use Exception;

/**
 * Výjimka pro chyby související s převodem požadavku na jiný objekt.
 *
 * @package App\Exception
 */
class RequestTransformerException extends Exception
{
    public function __construct($message)
    {
        parent::__construct($message);
    }
}