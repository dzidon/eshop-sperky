<?php

namespace App\Service;

use App\Entity\ProductSection;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Tato třída je Twig global a řeší zobrazování obsahu v navigaci, který je potřeba dostávat z databáze
 *
 * @package App\Service
 */
class NavbarService
{
    private array $sections = [];
    private bool $sectionsLoaded = false;

    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * Vrátí všechny viditelné produktové sekce. První zavolání selectuje z databáze a ukládá výsledky
     * do pole $sections. Další zavolání vrátí pole $sections.
     *
     * @return array
     */
    public function getVisibleSections(): array
    {
        if (!$this->sectionsLoaded)
        {
            $this->sections = $this->entityManager->getRepository(ProductSection::class)->findAllVisible();
            $this->sectionsLoaded = true;
        }

        return $this->sections;
    }
}