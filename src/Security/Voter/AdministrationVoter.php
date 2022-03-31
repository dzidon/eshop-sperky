<?php

namespace App\Security\Voter;

use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

class AdministrationVoter implements VoterInterface
{
    const SECTION_PERMISSION_OVERVIEW = 'admin_permission_overview';
    const SECTION_USER_MANAGEMENT = 'admin_user_management';
    const SECTION_PRODUCT_MANAGEMENT = 'admin_products';
    const SECTION_PRODUCT_SECTION_MANAGEMENT = 'admin_product_sections';
    const SECTION_PRODUCT_CATEGORY_MANAGEMENT = 'admin_product_categories';
    const SECTION_PRODUCT_OPTION_MANAGEMENT = 'admin_product_options';
    const SECTION_PRODUCT_INFO_MANAGEMENT = 'admin_product_info';
    const SECTION_DELIVERY_METHOD_MANAGEMENT = 'admin_delivery_methods';
    const SECTION_PAYMENT_METHOD_MANAGEMENT = 'admin_payment_methods';

    const REQUIRED_PERMISSIONS = [
        self::SECTION_PERMISSION_OVERVIEW => '_any',
        self::SECTION_USER_MANAGEMENT => [
            'user_edit_credentials', 'user_block_reviews', 'user_set_permissions'
        ],
        self::SECTION_PRODUCT_MANAGEMENT => [
            'product_edit', 'product_delete'
        ],
        self::SECTION_PRODUCT_SECTION_MANAGEMENT => [
            'product_section_edit', 'product_section_delete'
        ],
        self::SECTION_PRODUCT_CATEGORY_MANAGEMENT => [
            'product_category_edit', 'product_category_delete'
        ],
        self::SECTION_PRODUCT_OPTION_MANAGEMENT => [
            'product_option_edit', 'product_option_delete'
        ],
        self::SECTION_PRODUCT_INFO_MANAGEMENT => [
            'product_info_edit', 'product_info_delete'
        ],
        self::SECTION_DELIVERY_METHOD_MANAGEMENT => [
            'delivery_method_edit',
        ],
        self::SECTION_PAYMENT_METHOD_MANAGEMENT => [
            'payment_method_edit',
        ],
    ];

    /**
     * {@inheritdoc}
     */
    public function vote(TokenInterface $token, $subject, array $attributes): int
    {
        $user = $token->getUser();
        if (!$user instanceof User)
        {
            return self::ACCESS_DENIED;
        }

        $vote = self::ACCESS_ABSTAIN;
        foreach ($attributes as $attribute)
        {
            if (isset(self::REQUIRED_PERMISSIONS[$attribute]))
            {
                $vote = self::ACCESS_DENIED;
                $acceptablePermissions = self::REQUIRED_PERMISSIONS[$attribute];

                if ($acceptablePermissions === '_any')
                {
                    if (!$user->getPermissions()->isEmpty())
                    {
                        return self::ACCESS_GRANTED;
                    }
                }
                else if (is_array($acceptablePermissions))
                {
                    foreach ($acceptablePermissions as $acceptablePermission)
                    {
                        if ($user->hasPermission($acceptablePermission))
                        {
                            return self::ACCESS_GRANTED;
                        }
                    }
                }
            }
        }

        return $vote;
    }
}