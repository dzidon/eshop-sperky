<?php

namespace App\Security\Voter;

use App\Entity\Address;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class AddressVoter extends Voter
{
    const EDIT = 'address_edit';
    const DELETE = 'address_delete';

    /**
     * {@inheritdoc}
     */
    protected function supports(string $attribute, $subject): bool
    {
        if (!in_array($attribute, [self::EDIT, self::DELETE]))
        {
            return false;
        }

        if (!$subject instanceof Address)
        {
            return false;
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    protected function voteOnAttribute(string $attribute, $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();
        if (!$user instanceof User)
        {
            return false;
        }

        /** @var Address $address */
        $address = $subject;

        return $address->getUser() === $user;
    }
}