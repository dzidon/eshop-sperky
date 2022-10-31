<?php

namespace App\Command;

use App\Entity\TextContent;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

/**
 * Příkaz pro vytvoření chybějících TextContent entit v DB podle parametru app_text_content v services.yaml.
 *
 * @package App\Command
 */
class RefreshTextContentCommand extends Command
{
    protected static $defaultName = 'app:refresh-text-content';
    protected static $defaultDescription = 'Creates non-existent TextContent entities in the database.';

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
        if (!$this->parameterBag->has('app_text_content'))
        {
            $output->writeln('Parameter app_text_content not found in services.yaml!');
            return Command::FAILURE;
        }

        $configData = $this->parameterBag->get('app_text_content');
        if (!array_key_exists('defaults', $configData))
        {
            $output->writeln('Parameter app_text_content must contain the following key: defaults');
            return Command::FAILURE;
        }

        if (!array_key_exists('texts', $configData['defaults']) || !array_key_exists('entities', $configData['defaults']))
        {
            $output->writeln('Parameter app_text_content.defaults must contain the following keys: texts, entities');
            return Command::FAILURE;
        }

        if (!is_array($configData['defaults']['texts']))
        {
            $output->writeln('Parameter app_text_content.defaults.texts must be an array!');
            return Command::FAILURE;
        }

        if (!is_array($configData['defaults']['entities']))
        {
            $output->writeln('Parameter app_text_content.defaults.entities must be an array!');
            return Command::FAILURE;
        }

        $existingTextContents = $this->entityManager->getRepository(TextContent::class)->findAll();
        $newTextContents = [];

        foreach ($configData['defaults']['entities'] as $textContentName => $defaultTextName)
        {
            /** @var TextContent $existingTextContent */
            foreach ($existingTextContents as $existingTextContent)
            {
                if ($existingTextContent->getName() === $textContentName)
                {
                    continue 2;
                }
            }

            $newTextContent = new TextContent();
            $newTextContent->setName($textContentName);
            $newTextContent->setText($configData['defaults']['texts'][$defaultTextName]);

            $newTextContents[] = $newTextContent;
            $this->entityManager->persist($newTextContent);
        }

        if (empty($newTextContents))
        {
            $output->writeln('All TextContent entities are up-to-date!');
        }
        else
        {
            $this->entityManager->flush();

            $newTextContentNames = array_map(function (TextContent $newTextContent) {
                return $newTextContent->getName();
            }, $newTextContents);

            $output->writeln(sprintf('The following TextContent entities have been created: %s', implode(', ', $newTextContentNames)));
        }

        return Command::SUCCESS;
    }
}