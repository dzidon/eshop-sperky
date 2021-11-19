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
     *
     * @return $this
     */
    public function addRoute(string $route, array $parameters = [], string $title = ''): self
    {
        if(mb_strlen($title, 'utf-8') === 0)
        {
            $title = (string) $this->parameterBag->get('app_page_title.' . $route);
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
     *
     * @return $this
     */
    public function setPageTitleByRoute(string $route): self
    {
        $this->currentTitle = (string) $this->parameterBag->get('app_page_title.' . $route);

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