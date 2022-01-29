<?php

namespace App\Security\Voter;

use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

class PermissionVoter implements VoterInterface
{
    const CATEGORY_REVIEWS = 'Recenze';
    const CATEGORY_USERS = 'Správa uživatelů';
    const CATEGORY_PRODUCT_SECTIONS = 'Správa produktových sekcí';

    /*
     * (!!!) Po úpravě téhle konstanty je nutné vyvolat příkaz 'php bin/console app:refresh-permissions', aby se aktualizoval obsah tabulky 'permission'
     */
    public const PERMISSIONS = [
        //recenze
        'review_edit' => [
            'name' => 'Editace recenzí',
            'category' => self::CATEGORY_REVIEWS,
        ],
        'review_delete' => [
            'name' => 'Mazání recenzí',
            'category' => self::CATEGORY_REVIEWS,
        ],

        //sprava uzivatelu
        'user_edit_credentials' => [
            'name' => 'Editace osobních údajů uživatelů',
            'category' => self::CATEGORY_USERS,
        ],
        'user_block_reviews' => [
            'name' => 'Zablokování možnosti napsání recenze uživatelů',
            'category' => self::CATEGORY_USERS,
        ],
        'user_set_permissions' => [
            'name' => 'Nastavení oprávnění uživatelů',
            'category' => self::CATEGORY_USERS,
        ],

        //sprava produktovych sekci
        'product_section_edit' => [
            'name' => 'Tvorba a editace produktových sekcí',
            'category' => self::CATEGORY_PRODUCT_SECTIONS,
        ],
        'product_section_delete' => [
            'name' => 'Mazání produktových sekcí',
            'category' => self::CATEGORY_PRODUCT_SECTIONS,
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