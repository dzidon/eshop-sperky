<?php

namespace App\Exception;

use Exception;
use Symfony\Component\Validator\ConstraintViolationListInterface;

/**
 * Výjimka pro chyby související s převodem požadavku na jiný objekt.
 *
 * @package App\Exception
 */
class RequestTransformerException extends Exception
{
    private ?ConstraintViolationListInterface $validatorErrors;

    public function __construct($message, ConstraintViolationListInterface $validatorErrors = null)
    {
        parent::__construct($message);

        $this->validatorErrors = $validatorErrors;
    }

    public function getValidatorErrors(): ?ConstraintViolationListInterface
    {
        return $this->validatorErrors;
    }
}