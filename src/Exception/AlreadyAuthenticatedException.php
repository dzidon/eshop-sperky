<?php


namespace App\Exception;

use Symfony\Component\Security\Core\Exception\AuthenticationException;

class AlreadyAuthenticatedException extends AuthenticationException
{
    /**
     * {@inheritdoc}
     */
    public function getMessageKey()
    {
        return 'You already are logged in.';
    }
}