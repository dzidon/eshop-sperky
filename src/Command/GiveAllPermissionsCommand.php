<?php

namespace App\Command;

use App\Entity\Permission;
use App\Entity\User;
use Psr\Log\LoggerInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Konzolový příkaz, který udělí všechna oprávnění uživateli s e-mailem david.zidon@seznam.cz.
 *
 * @package App\Command
 */
class GiveAllPermissionsCommand extends Command
{
    private const USER_EMAIL = 'david.zidon@seznam.cz';

    protected static $defaultName = 'app:give-all-permissions';
    protected static $defaultDescription = 'Gives all permissions to someone.';

    private LoggerInterface $logger;
    private EntityManagerInterface $entityManager;

    public function __construct(LoggerInterface $logger, EntityManagerInterface $entityManager)
    {
        $this->logger = $logger;
        $this->entityManager = $entityManager;

        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        /** @var User $user */
        $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => self::USER_EMAIL]);
        if ($user === null)
        {
            $message = sprintf('Could not find a user with e-mail %s.', self::USER_EMAIL);
        }
        else
        {
            $permissions = $this->entityManager->getRepository(Permission::class)->findAll();
            /** @var Permission $permission */
            foreach ($permissions as $permission)
            {
                $user->addPermission($permission);
            }
            $this->entityManager->persist($user);
            $this->entityManager->flush();

            $message = sprintf('All permissions given to %s.', self::USER_EMAIL);
        }

        $output->writeln($message);
        $this->logger->info($message);

        return Command::SUCCESS;
    }
}