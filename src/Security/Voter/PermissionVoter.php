<?php

namespace App\Security\Voter;

use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

class PermissionVoter implements VoterInterface
{
    const CATEGORY_REVIEWS = 'Recenze';

    /*
     * (!!!) Po úpravě téhle konstanty je nutné vyvolat příkaz 'php bin/console app:refresh-permissions', aby se aktualizoval obsah tabulky 'permission'
     */
    public const PERMISSIONS = [
        'review_edit' => [
            'name' => 'Editace recenzí',
            'category' => self::CATEGORY_REVIEWS,
        ],
        'review_delete' => [
            'name' => 'Mazání recenzí',
            'category' => self::CATEGORY_REVIEWS,
        ],
    ];

    /**
     * {@inheritdoc}
     */
    public function vote(TokenInterface $token, $subject, array $attributes): int
    {
        $user = $token->getUser();
        if(!$user instanceof User)
        {
            return self::ACCESS_DENIED;
        }

        $vote = self::ACCESS_ABSTAIN;
        foreach ($attributes as $attribute)
        {
            if(isset(self::PERMISSIONS[$attribute]))
            {
                $vote = self::ACCESS_DENIED;

                if($user->hasPermission($attribute))
                {
                    return self::ACCESS_GRANTED;
                }
            }
        }

        return $vote;
    }
}