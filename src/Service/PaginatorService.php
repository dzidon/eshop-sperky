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
    public function initialize(Query $query, int $pageSize, int $page): self
    {
        $this->pageSize = $pageSize;
        $this->setCurrentPageAndSanitize($page);

        $paginator = new Paginator($query);
        $totalItems = count($paginator);
        $this->pagesCount = ceil($totalItems / $this->pageSize);

        $this->currentPageObjects = $paginator->getQuery()
            ->setFirstResult($this->pageSize * ($this->currentPage-1))
            ->setMaxResults($this->pageSize)
            ->getResult();

        return $this;
    }

    /**
     * Vrátí data pro vykreslení view
     *
     * @return array
     */
    public function getViewData(): array
    {
        return $this->viewData;
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
     * Sestaví data pro view
     */
    public function createViewData(): self
    {
        $slotsSurroundingCenter = self::VIEW_INNER_PAGES - self::VIEW_INNER_CENTER; //2
        for($i = 1; $i <= $slotsSurroundingCenter; $i++) // 1, 2
        {
            $this->exploreSurroundings($i);
        }

        //Stránky před aktuální stránkou
        if($this->viewCurrentPageSurroundings['before'] > 0)
        {
            $minimum = $this->currentPage - $this->viewCurrentPageSurroundings['before'];
            for ($i = $this->currentPage - 1; $i >= $minimum; $i--)
            {
                if(!$this->addViewPage($i, true)) break;
            }
        }

        // Aktuální stránka
        $this->addViewPage($this->currentPage, false);

        // Stránky po aktuální stránce
        if($this->viewCurrentPageSurroundings['after'] > 0)
        {
            $maximum = $this->currentPage + $this->viewCurrentPageSurroundings['after'];
            for ($i = $this->currentPage + 1; $i <= $maximum; $i++)
            {
                if(!$this->addViewPage($i, false)) break;
            }
        }

        // Vykreslení první a poslední stránky pokud to dává smysl
        $this->addAdditionalViewData();

        return $this;
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
     * @param bool $prepend
     * @param bool $isDivider
     * @return bool
     */
    private function addViewPage(int $page, bool $prepend, bool $isDivider = false): bool
    {
        if(!$isDivider)
        {
            if($this->isPageOutOfBounds($page))
            {
                return false;
            }
        }

        $functionBasedOnPrepend = [
            true => 'array_unshift',
            false => 'array_push',
        ];

        $functionBasedOnPrepend[$prepend] ($this->viewData, [
            'number' => $page,
            'isCurrent' => $page === $this->currentPage,
            'isDivider' => $isDivider,
        ]);
        return true;
    }

    /**
     * Zjistí, jestli je v zadaném okolí validní stránka. Pokud tam není, koukne se na druhou stranu.
     *
     * @param int $currentPageOffset
     */
    private function exploreSurroundings(int $currentPageOffset)
    {
        //před
        if($this->isPageOutOfBounds($this->currentPage-$currentPageOffset) && !$this->isPageOutOfBounds($this->currentPage+$currentPageOffset))
        {
            $this->viewCurrentPageSurroundings['after']++;
        }
        else if (!$this->isPageOutOfBounds($this->currentPage-$currentPageOffset))
        {
            $this->viewCurrentPageSurroundings['before']++;
        }

        //po
        if($this->isPageOutOfBounds($this->currentPage+$currentPageOffset) && !$this->isPageOutOfBounds($this->currentPage-$currentPageOffset))
        {
            $this->viewCurrentPageSurroundings['before']++;
        }
        else if (!$this->isPageOutOfBounds($this->currentPage+$currentPageOffset))
        {
            $this->viewCurrentPageSurroundings['after']++;
        }
    }

    /**
     * Pokud to má smysl, přidá ke stránkování ještě první a poslední stránku
     */
    private function addAdditionalViewData()
    {
        //Divider a úplně první stránka (pokud má smysl to renderovat)
        if(isset($this->viewData[array_key_first($this->viewData)]['number']))
        {
            $difference = $this->viewData[array_key_first($this->viewData)]['number'] - 1;
            if($difference == 1)
            {
                $this->addViewPage(1, true);
            }
            else if($difference == 2)
            {
                $this->addViewPage(2, true);
                $this->addViewPage(1, true);
            }
            else if($difference > 2)
            {
                $this->addViewPage(0, true, true);
                $this->addViewPage(1, true);
            }
        }

        //Divider a úplně poslední stránka (pokud má smysl to renderovat)
        if(isset($this->viewData[array_key_last($this->viewData)]['number']))
        {
            $difference = $this->pagesCount - $this->viewData[array_key_last($this->viewData)]['number'];
            if($difference == 1)
            {
                $this->addViewPage($this->pagesCount, false);
            }
            else if($difference == 2)
            {
                $this->addViewPage($this->pagesCount-1, false);
                $this->addViewPage($this->pagesCount, false);
            }
            else if($difference > 2)
            {
                $this->addViewPage(0, false, true);
                $this->addViewPage($this->pagesCount, false);
            }
        }
    }
}