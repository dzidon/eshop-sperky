<?php

namespace App\Command;

use App\Entity\PaymentMethod;
use App\Entity\Product;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Konzolový příkaz, který v DB vytvoří chybějící platební metody.
 *
 * @package App\Command
 */
class CreatePaymentMethodsCommand extends Command
{
    protected static $defaultName = 'app:create-payment-methods';
    protected static $defaultDescription = 'Creates payment methods in the DB.';

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
        $paymentMethods = [];
        $paymentMethods[] = (new PaymentMethod())
            ->setType(PaymentMethod::TYPE_ON_DELIVERY)
            ->setName('Platba dobírkou')
            ->setPriceWithoutVat(65.0)
            ->setPriceWithVat(65.0)
            ->setVat(Product::VAT_NONE)
        ;

        $paymentMethods[] = (new PaymentMethod())
            ->setType(PaymentMethod::TYPE_CARD)
            ->setName('Platba kartou')
            ->setPriceWithoutVat(0.0)
            ->setPriceWithVat(0.0)
            ->setVat(Product::VAT_NONE)
        ;

        $paymentMethods[] = (new PaymentMethod())
            ->setType(PaymentMethod::TYPE_TRANSFER)
            ->setName('Platba převodem')
            ->setPriceWithoutVat(0.0)
            ->setPriceWithVat(0.0)
            ->setVat(Product::VAT_NONE)
        ;

        $created = [];

        /** @var PaymentMethod $paymentMethod */
        foreach ($paymentMethods as $paymentMethod)
        {
            $paymentMethodInDb = $this->entityManager->getRepository(PaymentMethod::class)->findOneBy(['type' => $paymentMethod->getType()]);
            if ($paymentMethodInDb === null)
            {
                $this->entityManager->persist($paymentMethod);
                $created[] = $paymentMethod->getType();
            }
        }
        $this->entityManager->flush();

        if(count($created) === 0)
        {
            $output->writeln('No new payment methods have been created.');
        }
        else
        {
            $output->writeln(sprintf('Payment methods created: %s', implode(', ', $created)));
        }

        return Command::SUCCESS;
    }
}