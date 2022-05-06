<?php

namespace App\Exception;

use Exception;

/**
 * Výjimka pro chyby související s platbami
 *
 * @package App\Exception
 */
class PaymentException extends Exception
{
    public function __construct($message)
    {
        parent::__construct($message);
    }
}