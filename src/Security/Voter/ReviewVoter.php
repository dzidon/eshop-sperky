<?php

namespace App\Security\Voter;

use App\Entity\Review;
use App\Entity\User;
use LogicException;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class ReviewVoter extends Voter
{
    const EDIT = 'review_edit';
    const DELETE = 'review_delete';

    /**
     * {@inheritdoc}
     */
    protected function supports(string $attribute, $subject): bool
    {
        if (!in_array($attribute, [self::EDIT, self::DELETE]))
        {
            return false;
        }

        if (!$subject instanceof Review)
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

        /** @var Review $review */
        $review = $subject;

        switch ($attribute)
        {
            case self::EDIT:
                return $this->canEdit($review, $user);
            case self::DELETE:
                return $this->canDelete($review, $user);
        }

        throw new LogicException('This code should not be reached!');
    }

    private function canEdit(Review $review, User $user): bool
    {
        return $review->getUser() === $user; //TODO: admin permission
    }

    private function canDelete(Review $review, User $user): bool
    {
        return $review->getUser() === $user; //TODO: admin permission
    }
}