<?php

namespace App\Command;

use App\Entity\Permission;
use App\Security\Voter\PermissionVoter;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Konzolový příkaz, který aktualizuje oprávnění v db podle PermissionVoter::PERMISSIONS a zobrazí informace o počtu
 * vytvořených a aktualizovaných oprávnění v db.
 *
 * @package App\Command
 */
class RefreshPermissionsCommand extends Command
{
    protected static $defaultName = 'app:refresh-permissions';
    protected static $defaultDescription = 'Updates the "permission" table in the database so that is matches PermissionVoter::PERMISSIONS.';

    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;

        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $stats = [
            'created' => array(),
            'updated' => array(),
        ];

        foreach(PermissionVoter::PERMISSIONS as $attribute => $data)
        {
            $permissionInDb = $this->entityManager->getRepository(Permission::class)->findOneBy(['code' => $attribute]);
            $permissionHere = $this->entityManager->getRepository(Permission::class)->createNew($attribute, PermissionVoter::PERMISSIONS[$attribute]['name'], PermissionVoter::PERMISSIONS[$attribute]['category']);

            if($permissionInDb === null) //neexistuje opravneni s hledanym kodem
            {
                $this->entityManager->persist($permissionHere);
                $this->entityManager->flush();

                $stats['created'][] = $permissionHere->getCode();
            }
            else if($permissionInDb->getName() !== $permissionHere->getName() || $permissionInDb->getCategory() !== $permissionHere->getCategory()) //opravneni s danym kodem existuje, ma ale neaktualni data, tak je updatneme
            {
                $permissionInDb->setName( $permissionHere->getName() );
                $permissionInDb->setCategory( $permissionHere->getCategory() );
                $this->entityManager->flush();

                $stats['updated'][] = $permissionInDb->getCode();
            }
        }

        $numberOfCreated = count($stats['created']);
        $numberOfUpdated = count($stats['updated']);
        if(($numberOfCreated + $numberOfUpdated) === 0)
        {
            $output->writeln('Nothing has been updated, your table "permission" is up-to-date!');
        }
        else
        {
            $output->writeln(['Your table "permission" has been successfully updated!', '']);
            if($numberOfCreated > 0)
            {
                $output->writeln(sprintf('Permissions created: %s', implode(', ', $stats['created'])));
            }
            if($numberOfUpdated > 0)
            {
                $output->writeln(sprintf('Permissions updated: %s', implode(', ', $stats['updated'])));
            }
        }

        return Command::SUCCESS;
    }
}