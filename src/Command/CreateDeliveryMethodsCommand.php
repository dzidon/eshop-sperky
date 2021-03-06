<?php

namespace App\Command;

use App\Entity\DeliveryMethod;
use App\Entity\Product;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Konzolový příkaz, který v DB vytvoří chybějící doručovací metody.
 *
 * @package App\Command
 */
class CreateDeliveryMethodsCommand extends Command
{
    protected static $defaultName = 'app:create-delivery-methods';
    protected static $defaultDescription = 'Creates delivery methods in the DB.';

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
        $deliveryMethods = [];
        $deliveryMethods[] = (new DeliveryMethod())
            ->setType(DeliveryMethod::TYPE_CZECH_POST)
            ->setName('Česká pošta')
            ->setPriceWithoutVat(65.0)
            ->setPriceWithVat(65.0)
            ->setVat(Product::VAT_NONE)
            ->setLocksDeliveryAddress(false)
        ;

        $deliveryMethods[] = (new DeliveryMethod())
            ->setType(DeliveryMethod::TYPE_PACKETA_CZ)
            ->setName('Zásilkovna CZ')
            ->setPriceWithoutVat(65.0)
            ->setPriceWithVat(65.0)
            ->setVat(Product::VAT_NONE)
            ->setLocksDeliveryAddress(true)
        ;

        $created = [];

        /** @var DeliveryMethod $deliveryMethod */
        foreach ($deliveryMethods as $deliveryMethod)
        {
            $deliveryMethodInDb = $this->entityManager->getRepository(DeliveryMethod::class)->findOneBy(['type' => $deliveryMethod->getType()]);
            if ($deliveryMethodInDb === null)
            {
                $this->entityManager->persist($deliveryMethod);
                $created[] = $deliveryMethod->getType();
            }
        }
        $this->entityManager->flush();

        if(count($created) === 0)
        {
            $output->writeln('No new delivery methods have been created.');
        }
        else
        {
            $output->writeln(sprintf('Delivery methods created: %s', implode(', ', $created)));
        }

        return Command::SUCCESS;
    }
}