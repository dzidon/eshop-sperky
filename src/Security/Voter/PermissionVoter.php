<?php

namespace App\Security\Voter;

use App\Entity\Permission;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class PermissionVoter implements VoterInterface
{
    private EntityManagerInterface $entityManager;

    const CATEGORY_REVIEWS = 'reviews';

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

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * {@inheritdoc}
     */
    public function vote(TokenInterface $token, $subject, array $attributes): int
    {
        $vote = self::ACCESS_ABSTAIN;

        if($subject === null)
        {
            foreach ($attributes as $attribute)
            {
                if(isset(self::PERMISSIONS[$attribute]))
                {
                    $vote = self::ACCESS_DENIED;

                    if($this->userHasPermission($token->getUser(), $attribute))
                    {
                        return self::ACCESS_GRANTED;
                    }
                }
            }
        }

        return $vote;
    }

    /**
     * Kontrola, jestli má uživatel nastavené oprávnění podle zadaného kódu.
     *
     * @param UserInterface $user
     * @param string $attribute
     * @return bool
     */
    private function userHasPermission(UserInterface $user, string $attribute): bool
    {
        $userPermissions = $user->getPermissions();

        foreach ($userPermissions as $permission)
        {
            if($permission->getCode() === $attribute)
            {
                return true;
            }
        }
        $this->updatePermissionInDb($attribute);

        return false;
    }

    /**
     * Pokud neexistuje žádné oprávnění se zadaným kódem, vytvoří se nové v databázi. Pokud už existuje v db, ale nerovná se
     * tomu, co je v PERMISSIONS, oprávnění v db se aktualizuje podle PERMISSIONS.
     *
     * @param string $attribute
     */
    private function updatePermissionInDb(string $attribute)
    {
        $permissionInDb = $this->entityManager->getRepository(Permission::class)->findOneBy(['code' => $attribute]);
        $permissionHere = $this->entityManager->getRepository(Permission::class)->createNew($attribute, self::PERMISSIONS[$attribute]['name'], self::PERMISSIONS[$attribute]['category']);

        if($permissionInDb === null) //neexistuje opravneni s hledanym kodem
        {
            $this->entityManager->persist($permissionHere);
            $this->entityManager->flush();
        }
        else if($permissionInDb->getName() !== $permissionHere->getName() || $permissionInDb->getCategory() !== $permissionHere->getCategory()) //opravneni s danym kodem existuje, ma ale neaktualni data, tak je updatneme
        {
            $permissionInDb->setName( $permissionHere->getName() );
            $permissionInDb->setCategory( $permissionHere->getCategory() );
            $this->entityManager->flush();
        }
    }
}