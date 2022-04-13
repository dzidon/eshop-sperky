<?php

namespace App\Command;

use App\Entity\Order;
use Psr\Log\LoggerInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Konzolový příkaz, který v DB smaže dlouho neaktivní košíky.
 *
 * @package App\Command
 */
class DeleteInactiveCartsCommand extends Command
{
    protected static $defaultName = 'app:delete-inactive-carts';
    protected static $defaultDescription = 'Deletes inactive carts in the DB.';

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
        $countDeleted = $this->entityManager->getRepository(Order::class)->deleteInactiveCartOrders();

        $message = sprintf('%d inactive cart orders have been removed.', $countDeleted);
        $output->writeln($message);
        $this->logger->info($message);

        return Command::SUCCESS;
    }
}