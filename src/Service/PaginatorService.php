<?php


namespace App\Service;

use Doctrine\ORM\Query;
use Doctrine\ORM\Tools\Pagination\Paginator;

class PaginatorService
{
    private const VIEW_INNER_PAGES = 5;
    private const VIEW_INNER_CENTER = 3;

    /**
     * @var int Počet prvků na jedné stránce
     */
    private int $pageSize;

    /**
     * @var int Číslo aktuální stránky
     */
    private int $currentPage;

    /**
     * @var float Počet všech stránek
     */
    private float $pagesCount;

    /**
     * @var array Objekty zobrazující se na aktuální stránce
     */
    private array $currentPageObjects;

    /**
     * @var array Data pro klikatelné stránkování
     */
    private array $viewData = [];

    /**
     * @var array|int[] Informace o tom, kolik je validních stránek před a po aktuální stránce
     */
    private array $viewCurrentPageSurroundings = [
        'before' => 0,
        'after' => 0,
    ];

    /**
     * Vezme dotaz, požadovaný počet prvků na stránku, aktuální stránku a získá pouze prvky patřící na danou stránku.
     * Zároveň sestaví data potřebná pro vykreslení stránkování.
     *
     * @param Query $query
     * @param int $pageSize
     * @param int $page
     * @return $this
     */
    public function build(Query $query, int $pageSize, int $page): self
    {
        $this->pageSize = $pageSize;
        $this->setCurrentPageAndSanitize($page);

        $paginator = new Paginator($query);
        $totalItems = count($paginator);
        $this->pagesCount = ceil($totalItems / $this->pageSize);

        $this->createViewData();

        $this->currentPageObjects = $paginator->getQuery()
            ->setFirstResult($this->pageSize * ($this->currentPage-1))
            ->setMaxResults($this->pageSize)
            ->getResult();

        return $this;
    }

    /**
     * Vrátí počet objektů na stránku
     *
     * @return int
     */
    public function getPageSize(): int
    {
        return $this->pageSize;
    }

    /**
     * Vrátí objekty patřící na aktuální stránku
     *
     * @return array
     */
    public function getCurrentPageObjects(): array
    {
        return $this->currentPageObjects;
    }

    /**
     * Vrátí aktuální stránku
     *
     * @return int
     */
    public function getCurrentPage(): int
    {
        return $this->currentPage;
    }

    /**
     * Zjistí, jestli je požadovaná stránka větší než celkový počet stránek, nebo jestli je aktuální stránka menší než 1
     *
     * @param int $page
     * @return bool
     */
    public function isPageOutOfBounds(int $page): bool
    {
        return (($page > $this->pagesCount && $page !== 1) || $page < 1);
    }

    /**
     * Nastaví aktuální stránku a zároveň zajistí, že nebude menší než 1
     *
     * @param int $page
     */
    private function setCurrentPageAndSanitize(int $page)
    {
        if($page > 0)
        {
            $this->currentPage = $page;
        }
        else
        {
            $this->currentPage = 1;
        }
    }

    /**
     * Přidá číslo stránky do view spolu s informací o tom, zda se jedná o aktuálně navštívenou stránku
     *
     * @param int $page
     */
    private function addViewPage(int $page)
    {
        $this->viewData[] = [
            'pageNumber' => $page,
            'isCurrent' => $page === $this->currentPage,
        ];
    }

    /**
     * Zjistí, jestli je v zadaném okolí validní stránka. Pokud tam není, koukne se na druhou stranu.
     *
     * @param int $currentPageOffset
     * @param bool $before
     */
    private function exploreSurroundings(int $currentPageOffset, bool $before)
    {
        if($before)
        {
            if($this->isPageOutOfBounds($this->currentPage-$currentPageOffset) && !$this->isPageOutOfBounds($this->currentPage+$currentPageOffset))
            {
                $this->viewCurrentPageSurroundings['after']++;
            }
            else
            {
                $this->viewCurrentPageSurroundings['before']++;
            }
        }
        else
        {
            if($this->isPageOutOfBounds($this->currentPage+$currentPageOffset) && !$this->isPageOutOfBounds($this->currentPage-$currentPageOffset))
            {
                $this->viewCurrentPageSurroundings['before']++;
            }
            else
            {
                $this->viewCurrentPageSurroundings['after']++;
            }
        }
    }

    /**
     * Sestaví data pro view
     */
    private function createViewData()
    {
        $slotsSurroundingCenter = self::VIEW_INNER_PAGES - self::VIEW_INNER_CENTER; //2

        /*
         * Prozkoumá okolí
         */
        for($i = 1; $i <= $slotsSurroundingCenter; $i++) // 1, 2
        {
            $this->exploreSurroundings($i, true); //kontrola pred aktualni strankou
            $this->exploreSurroundings($i, false); //kontrola po aktualni strance
        }

        /*
         * Stránky před aktuální stránkou
         */
        if($this->viewCurrentPageSurroundings['before'] > 0)
        {
            $start = $this->currentPage - $this->viewCurrentPageSurroundings['before'];
            for ($i = $start; $i < $this->currentPage; $i++)
            {
                $this->addViewPage($i); // 1, 2
            }
        }

        /*
         * Aktuální stránka
         */
        $this->addViewPage($this->currentPage); // 3

        /*
         * Stránky po aktuální stránce
         */
        if($this->viewCurrentPageSurroundings['after'] > 0)
        {
            $maximum = $this->currentPage + $this->viewCurrentPageSurroundings['after'];
            for ($i = $this->currentPage + 1; $i <= $maximum; $i++)
            {
                $this->addViewPage($i); // 4 5
            }
        }
    }
}