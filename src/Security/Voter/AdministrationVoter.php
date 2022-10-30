<?php

namespace App\Security\Voter;

use App\Entity\User;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

class AdministrationVoter implements VoterInterface
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

        if ($this->parameterBag->has('app_admin_routes_required_permissions'))
        {
            $requiredPermissions = $this->parameterBag->get('app_admin_routes_required_permissions');

            foreach ($attributes as $attribute)
            {
                if ($user instanceof User && array_key_exists($attribute, $requiredPermissions))
                {
                    $vote = self::ACCESS_DENIED;
                    $acceptablePermissions = $requiredPermissions[$attribute];

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
        }

        return $vote;
    }
}