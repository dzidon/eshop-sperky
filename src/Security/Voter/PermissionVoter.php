<?php

namespace App\Security\Voter;

use App\Entity\User;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

class PermissionVoter implements VoterInterface
{
    private ParameterBagInterface $parameterBag;

    public function __construct(ParameterBagInterface $parameterBag)
    {
        $this->parameterBag = $parameterBag;
    }

    /**
     * {@inheritdoc}
     */
    public function vote(TokenInterface $token, $subject, array $attributes): int
    {
        $user = $token->getUser();
        $vote = self::ACCESS_ABSTAIN;

        if ($this->parameterBag->has('app_permissions'))
        {
            $permissions = $this->parameterBag->get('app_permissions');

            foreach ($attributes as $attribute) // vetsinou jen jedna iterace, protoze volame treba 'IsGranted("product_info_edit")'
            {
                if ($user instanceof User && array_key_exists($attribute, $permissions))
                {
                    $vote = self::ACCESS_DENIED;

                    if ($user->hasPermission($attribute))
                    {
                        return self::ACCESS_GRANTED;
                    }
                }
            }
        }

        return $vote;
    }
}