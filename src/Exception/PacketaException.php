<?php

namespace App\Exception;

use Exception;

/**
 * Výjimka pro chyby související s API Zásilkovny
 *
 * @package App\Exception
 */
class PacketaException extends Exception
{
    private array $errors;

    public function __construct(array $errors)
    {
        parent::__construct();

        $this->errors = $errors;
    }

    /**
     * @return array
     */
    public function getErrors(): array
    {
        return $this->errors;
    }
}