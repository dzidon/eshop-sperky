<?php

namespace App\Pagination;

use Doctrine\ORM\Query;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Symfony\Component\HttpFoundation\Request;

/**
 * Třída řešící stránkování
 *
 * @package App\Pagination
 */
class Pagination
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
     * @var int Celkový počet úplně všech nalezených prvků
     */
    private int $totalItems;

    /**
     * @var array Data pro klikatelné stránkování
     */
    private array $viewData = [];

    /**
     * Parametry požadavku, primárně GET, jdou do nich ale i přimíchat atributy pomocí addAttributesToPathParameters
     *
     * @var array
     */
    private array $queryParameters;

    /**
     * Všechny atributy požadavku
     *
     * @var array
     */
    private array $queryAttributes;

    /**
     * Název GET parametru, ve kterém je číslo aktuální stránky
     *
     * @var string
     */
    private string $pageParameterName;

    public function __construct(Query $query, Request $request, int $pageSize = 10, string $pageParameterName = 'page')
    {
        $this->queryParameters = $request->query->all();
        $this->queryAttributes = $request->attributes->all();
        $this->pageParameterName = $pageParameterName;

        $page = (int) $request->query->get($pageParameterName, '1');
        $this->setCurrentPageAndSanitize($page);
        $this->paginate($query, $pageSize);
    }

    /**
     * Vrátí název GET parametru, ve kterém je číslo aktuální stránky
     *
     * @return string
     */
    public function getPageParameterName(): string
    {
        return $this->pageParameterName;
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
     * Vrátí počet úplně všech nalezených prvků
     *
     * @return int
     */
    public function getTotalItems(): int
    {
        return $this->totalItems;
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
     * Do parametrů odkazů přimíchá hodnoty požadovaných atributů
     *
     * @param array $attributes
     * @return $this
     */
    public function addAttributesToPathParameters(array $attributes): self
    {
        foreach ($attributes as $wantedAttributeKey)
        {
            foreach($this->queryAttributes as $existingAttributeKey => $existingAttributeValue)
            {
                if($wantedAttributeKey === $existingAttributeKey)
                {
                    $this->queryParameters[$existingAttributeKey] = $existingAttributeValue;
                    break;
                }
            }
        }

        return $this;
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
     * Zjistí, jestli je aktuální stránka větší než celkový počet stránek, nebo jestli je aktuální stránka menší než 1
     *
     * @return bool
     */
    public function isCurrentPageOutOfBounds(): bool
    {
        return $this->isPageOutOfBounds( $this->currentPage );
    }

    /**
     * Sestaví data pro view
     */
    public function createView(): self
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
     * Vezme dotaz, požadovaný počet prvků na stránku, aktuální stránku a získá pouze prvky patřící na danou stránku.
     * Zároveň sestaví data potřebná pro vykreslení stránkování.
     *
     * @param Query $query
     * @param int $pageSize
     */
    private function paginate(Query $query, int $pageSize): void
    {
        $this->pageSize = $pageSize;
        $paginator = new Paginator($query);
        $this->totalItems = count($paginator);
        $this->pagesCount = ceil($this->totalItems / $this->pageSize);

        $this->currentPageObjects = $paginator->getQuery()
            ->setFirstResult($this->pageSize * ($this->currentPage-1))
            ->setMaxResults($this->pageSize)
            ->getResult()
        ;
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

        $this->queryParameters[$this->pageParameterName] = $page;
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
            if(isset($pageData['queryParameters'][$this->pageParameterName]) && $pageData['queryParameters'][$this->pageParameterName] === $page)
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
        if(isset($this->viewData[array_key_first($this->viewData)]['queryParameters'][$this->pageParameterName]))
        {
            $difference = $this->viewData[array_key_first($this->viewData)]['queryParameters'][$this->pageParameterName] - 1;
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
        if(isset($this->viewData[array_key_last($this->viewData)]['queryParameters'][$this->pageParameterName]))
        {
            $difference = $this->pagesCount - $this->viewData[array_key_last($this->viewData)]['queryParameters'][$this->pageParameterName];
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