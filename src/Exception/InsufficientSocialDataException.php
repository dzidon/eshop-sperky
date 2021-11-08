<?php


namespace App\Exception;

use Symfony\Component\Security\Core\Exception\AuthenticationException;

class InsufficientSocialDataException extends AuthenticationException
{
    /**
     * {@inheritdoc}
     */
    public function getMessageKey()
    {
        return 'We are unable to get all the required data from your social login.';
    }
}