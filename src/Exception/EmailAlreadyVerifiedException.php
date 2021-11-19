<?php


namespace App\Exception;

use SymfonyCasts\Bundle\VerifyEmail\Exception\VerifyEmailExceptionInterface;

class EmailAlreadyVerifiedException extends \Exception implements VerifyEmailExceptionInterface
{
    /**
     * {@inheritdoc}
     */
    public function getReason(): string
    {
        return 'Your email address is already verified.';
    }
}