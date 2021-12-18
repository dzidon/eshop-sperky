<?php

namespace App\Service;

use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

/**
 * Třída BreadcrumbsService řeší drobečkovou navigaci
 *
 * @package App\Service
 */
class BreadcrumbsService
{
    private array $breadcrumbsData = [];
    private string $currentTitle = '';

    private ParameterBagInterface $parameterBag;

    public function __construct(ParameterBagInterface $parameterBag)
    {
        $this->parameterBag = $parameterBag;
    }

    /**
     * Přidá odkaz do breadcrumbs. Pokud je zadaný title prázdný string, automaticky se nastaví aktuální title podle
     * cesty z configu.
     *
     * @param string $route
     * @param array $parameters
     * @param string $title
     * @param string $variation
     * @return $this
     */
    public function addRoute(string $route, array $parameters = [], string $title = '', string $variation = ''): self
    {
        if(mb_strlen($title, 'utf-8') === 0)
        {
            $title = $this->parameterBag->get('app_page_title.' . $route);
            if(is_array($title))
            {
                $title = $title[$variation];
            }
            $title = (string) $title;
        }

        $this->breadcrumbsData[] = ['route' => $route, 'title' => $title, 'parameters' => $parameters];
        $this->currentTitle = $title;

        return $this;
    }

    public function getBreadcrumbsData(): array
    {
        return $this->breadcrumbsData;
    }

    public function getPageTitle(): string
    {
        return $this->currentTitle;
    }

    /**
     * Manuálně nastaví aktuální title z configu podle zadanáho názvu cesty.
     *
     * @param string $route
     * @param string $variation
     * @return $this
     */
    public function setPageTitleByRoute(string $route, string $variation = ''): self
    {
        $title = $this->parameterBag->get('app_page_title.' . $route);
        if(is_array($title))
        {
            $title = $title[$variation];
        }
        $this->currentTitle = (string) $title;

        return $this;
    }

    /**
     * Manuálně nastaví aktuální title na zadaný string.
     *
     * @param string $currentTitle
     *
     * @return $this
     */
    public function setPageTitle(string $currentTitle): self
    {
        $this->currentTitle = $currentTitle;

        return $this;
    }
}