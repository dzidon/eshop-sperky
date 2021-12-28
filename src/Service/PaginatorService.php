<?php

namespace App\Service;

use Doctrine\ORM\Query;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Symfony\Component\HttpFoundation\RequestStack;

class PaginatorService
{
    public const QUERY_PARAMETER_PAGE_NAME = 'stranka';

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
     * Parametry GET požadavku
     *
     * @var array
     */
    private array $queryParameters;

    public function __construct(RequestStack $requestStack)
    {
        $this->queryParameters = $requestStack->getCurrentRequest()->query->all();
    }

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
        $maxSlotsSurroundingCenter = self::VIEW_INNER_PAGES - self::VIEW_INNER_CENTER; //2
        $slotsLeftToCenter = $this->currentPage - 1;
        $slotsRightToCenter = $this->pagesCount - $this->currentPage;
        if($slotsLeftToCenter > $maxSlotsSurroundingCenter) $slotsLeftToCenter = $maxSlotsSurroundingCenter;
        if($slotsRightToCenter > $maxSlotsSurroundingCenter) $slotsRightToCenter = $maxSlotsSurroundingCenter;

        $directions = [
            'left' => [
                'offset' => -1,
                'next' => 'right',
                'allowedDistance' => $slotsLeftToCenter + ($maxSlotsSurroundingCenter - $slotsRightToCenter),
            ],
            'right' => [
                'offset' => 1,
                'next' => 'stop',
                'allowedDistance' => $slotsRightToCenter + ($maxSlotsSurroundingCenter - $slotsLeftToCenter),
            ],
            'stop' => [
                'offset' => 0,
            ],
        ];

        $direction = $directions['left'];
        $visitedPage = $this->currentPage;
        while(count($this->viewData) < self::VIEW_INNER_PAGES and $direction !== $directions['stop'])
        {
            if(!$this->viewContainsPage($visitedPage))
            {
                $this->addViewPage($visitedPage, $direction === $directions['left']);
            }

            $nextPage = $visitedPage + $direction['offset'];
            if($this->isPageOutOfBounds($nextPage) || abs($this->currentPage - $visitedPage) >= $direction['allowedDistance'])
            {
                $direction = $directions[$direction['next']];
                $visitedPage = $this->currentPage;
            }
            else
            {
                $visitedPage = $nextPage;
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

        $this->queryParameters[self::QUERY_PARAMETER_PAGE_NAME] = $page;
        $functionBasedOnPrepend[$prepend] ($this->viewData, [
            'queryParameters' => $this->queryParameters,
            'isCurrent' => $page === $this->currentPage,
            'isDivider' => $isDivider,
        ]);

        return true;
    }

    /**
     * Zjistí, jestli už je v datech o view požadovaná stránka
     *
     * @param int $page
     * @return bool
     */
    private function viewContainsPage(int $page): bool
    {
        foreach ($this->viewData as $pageData)
        {
            if(isset($pageData['queryParameters'][self::QUERY_PARAMETER_PAGE_NAME]) && $pageData['queryParameters'][self::QUERY_PARAMETER_PAGE_NAME] === $page)
            {
                return true;
            }
        }
        return false;
    }

    /**
     * Pokud to má smysl, přidá ke stránkování ještě první a poslední stránku
     */
    private function addAdditionalViewData()
    {
        //Divider a úplně první stránka (pokud má smysl to renderovat)
        if(isset($this->viewData[array_key_first($this->viewData)]['queryParameters'][self::QUERY_PARAMETER_PAGE_NAME]))
        {
            $difference = $this->viewData[array_key_first($this->viewData)]['queryParameters'][self::QUERY_PARAMETER_PAGE_NAME] - 1;
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
        if(isset($this->viewData[array_key_last($this->viewData)]['queryParameters'][self::QUERY_PARAMETER_PAGE_NAME]))
        {
            $difference = $this->pagesCount - $this->viewData[array_key_last($this->viewData)]['queryParameters'][self::QUERY_PARAMETER_PAGE_NAME];
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