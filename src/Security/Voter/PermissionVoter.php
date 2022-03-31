<?php

namespace App\Security\Voter;

use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

class PermissionVoter implements VoterInterface
{
    const CATEGORY_REVIEWS = 'Recenze';
    const CATEGORY_USERS = 'Uživatelé';
    const CATEGORY_PRODUCTS = 'Produkty';
    const CATEGORY_PRODUCT_SECTIONS = 'Produktové sekce';
    const CATEGORY_PRODUCT_CATEGORIES = 'Produktové kategore';
    const CATEGORY_PRODUCT_OPTIONS = 'Produktové volby';
    const CATEGORY_PRODUCT_INFO = 'Skupiny produktových informací';
    const CATEGORY_DELIVERY_PAYMENT_METHODS = 'Doručovací a platební metody';

    /*
     * (!!!) Po úpravě téhle konstanty je nutné vyvolat příkaz 'php bin/console app:refresh-permissions', aby se aktualizoval obsah tabulky 'permission'
     */
    public const PERMISSIONS = [

        // recenze
        'review_edit' => [
            'name' => 'Editace recenzí',
            'category' => self::CATEGORY_REVIEWS,
        ],
        'review_delete' => [
            'name' => 'Mazání recenzí',
            'category' => self::CATEGORY_REVIEWS,
        ],

        // sprava uzivatelu
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

        // sprava produktovych sekci
        'product_section_edit' => [
            'name' => 'Tvorba a editace produktových sekcí',
            'category' => self::CATEGORY_PRODUCT_SECTIONS,
        ],
        'product_section_delete' => [
            'name' => 'Mazání produktových sekcí',
            'category' => self::CATEGORY_PRODUCT_SECTIONS,
        ],

        // sprava produktovych kategorii
        'product_category_edit' => [
            'name' => 'Tvorba a editace produktových kategorií',
            'category' => self::CATEGORY_PRODUCT_CATEGORIES,
        ],
        'product_category_delete' => [
            'name' => 'Mazání produktových kategorií',
            'category' => self::CATEGORY_PRODUCT_CATEGORIES,
        ],

        // sprava produktovych voleb
        'product_option_edit' => [
            'name' => 'Tvorba a editace produktových voleb',
            'category' => self::CATEGORY_PRODUCT_OPTIONS,
        ],
        'product_option_delete' => [
            'name' => 'Mazání produktových voleb',
            'category' => self::CATEGORY_PRODUCT_OPTIONS,
        ],

        // sprava skupin produktovych informaci
        'product_info_edit' => [
            'name' => 'Tvorba a editace skupin produktových informací',
            'category' => self::CATEGORY_PRODUCT_INFO,
        ],
        'product_info_delete' => [
            'name' => 'Mazání skupin produktových informací',
            'category' => self::CATEGORY_PRODUCT_INFO,
        ],

        // sprava produktu
        'product_edit' => [
            'name' => 'Tvorba a editace produktů',
            'category' => self::CATEGORY_PRODUCTS,
        ],
        'product_delete' => [
            'name' => 'Mazání produktů',
            'category' => self::CATEGORY_PRODUCTS,
        ],

        // doručovací a platební metody
        'delivery_method_edit' => [
            'name' => 'Editace doručovacích metod',
            'category' => self::CATEGORY_DELIVERY_PAYMENT_METHODS,
        ],
        'payment_method_edit' => [
            'name' => 'Editace platebních metod',
            'category' => self::CATEGORY_DELIVERY_PAYMENT_METHODS,
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