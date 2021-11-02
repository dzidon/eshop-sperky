<?php


namespace App\Exception;

use SymfonyCasts\Bundle\VerifyEmail\Exception\VerifyEmailExceptionInterface;

class EmailAlreadyVerifiedException extends \Exception implements VerifyEmailExceptionInterface
{
    public function getReason(): string
    {
        return 'Your email address is already verified.';
    }
}