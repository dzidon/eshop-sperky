<?php


namespace App\Exception;

use Symfony\Component\Security\Core\Exception\AuthenticationException;

class VerifyLinkInvalidException extends AuthenticationException
{
    /**
     * {@inheritdoc}
     */
    public function getMessageKey()
    {
        return 'This verification link is invalid.';
    }
}