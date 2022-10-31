<?php

namespace App\Service;

use App\Entity\TextContent;
use Doctrine\ORM\EntityManagerInterface;
use LogicException;

/**
 * Třída pro načítání editovatelného textového obsahu.
 *
 * @package App\Service
 */
class TextContentLoader
{
    private array $textContents = [];

    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * Načte entity TextContent podle zadaných názvů a uloží je do privátního atributu.
     *
     * @param array $names
     * @return TextContent[]
     */
    public function preload(array $names): array
    {
        if (empty($names))
        {
            return [];
        }

        $textContents = $this->entityManager->getRepository(TextContent::class)->findByNames($names);

        /** @var TextContent $textContent */
        foreach ($textContents as $textContent)
        {
            $this->rememberTextContent($textContent);
        }

        return $textContents;
    }

    /**
     * Vrátí entitu TextContent s daným názvem. Pokud už v minulosti došlo k načtení entity TextContent s daným názvem,
     * nedojde k dotázání v DB. Znovunačtení z DB jde vynutit.
     *
     * @param string $name
     * @param bool $forceReload
     * @return TextContent
     */
    public function getTextContent(string $name, bool $forceReload = false): TextContent
    {
        if (array_key_exists($name, $this->textContents) && !$forceReload)
        {
            return $this->textContents[$name];
        }

        $textContent = $this->entityManager->getRepository(TextContent::class)->findOneBy(['name' => $name]);
        if ($textContent === null)
        {
            throw new LogicException(sprintf('Entita TextContent s názvem %s neexistuje.', $name));
        }

        $this->rememberTextContent($textContent);
        return $textContent;
    }

    /**
     * Uloží si entitu TextContent do pole v privátní proměnné pro budoucí použití.
     *
     * @param TextContent $content
     * @return void
     */
    private function rememberTextContent(TextContent $content): void
    {
        $this->textContents[$content->getName()] = $content;
    }
}