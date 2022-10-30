<?php

namespace App\Command;

use App\Entity\Permission;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

/**
 * Konzolový příkaz, který aktualizuje oprávnění v db podle "app_permissions" (services.yaml) a zobrazí informace o počtu
 * vytvořených a aktualizovaných oprávnění v db.
 *
 * @package App\Command
 */
class RefreshPermissionsCommand extends Command
{
    protected static $defaultName = 'app:refresh-permissions';
    protected static $defaultDescription = 'Updates the "permission" table in the database so that it matches "app_permissions" in services.yaml.';

    private EntityManagerInterface $entityManager;
    private ParameterBagInterface $parameterBag;

    public function __construct(EntityManagerInterface $entityManager, ParameterBagInterface $parameterBag)
    {
        $this->entityManager = $entityManager;
        $this->parameterBag = $parameterBag;

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

        if (!$this->parameterBag->has('app_permissions'))
        {
            $output->writeln('Parameter app_permissions not found in services.yaml!');
            return Command::FAILURE;
        }

        $permissions = $this->parameterBag->get('app_permissions');

        foreach ($permissions as $attribute => $data)
        {
            $permissionInDb = $this->entityManager->getRepository(Permission::class)->findOneBy(['code' => $attribute]);
            $permissionHere = new Permission();
            $permissionHere->setCode($attribute)
                           ->setName($data['name'])
                           ->setCategory($data['category']);

            if($permissionInDb === null) // neexistuje opravneni s hledanym kodem
            {
                $this->entityManager->persist($permissionHere);

                $stats['created'][] = $permissionHere->getCode();
            }
            else if($permissionInDb->getName() !== $permissionHere->getName() || $permissionInDb->getCategory() !== $permissionHere->getCategory()) //opravneni s danym kodem existuje, ma ale neaktualni data, tak je updatneme
            {
                $permissionInDb->setName( $permissionHere->getName() );
                $permissionInDb->setCategory( $permissionHere->getCategory() );
                $this->entityManager->persist($permissionInDb);

                $stats['updated'][] = $permissionInDb->getCode();
            }
        }

        $this->entityManager->flush();

        $numberOfCreated = count($stats['created']);
        $numberOfUpdated = count($stats['updated']);
        if(($numberOfCreated + $numberOfUpdated) === 0)
        {
            $output->writeln('All permissions are up-to-date!');
        }
        else
        {
            $output->writeln(['Permissions have been updated!', '']);
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