<?php

namespace App\Service;

use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

/**
 * Třída BreadcrumbsService řeší drobečkovou navigaci
 *
 * @package App\Service
 */
class Breadcrumbs
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
     * @param string $titleAppend
     * @return $this
     */
    public function addRoute(string $route, array $parameters = [], string $title = '', string $variation = '', string $titleAppend = ''): self
    {
        if ($title === '')
        {
            $title = $this->getPageTitleForRoute($route, $variation);
        }

        if (mb_strlen($titleAppend, 'utf-8') > 0)
        {
            $title .= ' ' . $titleAppend;
        }

        $this->breadcrumbsData[] = ['route' => $route, 'title' => $title, 'parameters' => $parameters];
        $this->currentTitle = $title;

        return $this;
    }

    /**
     * Vrátí data pro vykreslení drobečkové navigace.
     *
     * @return array
     */
    public function getBreadcrumbsData(): array
    {
        return $this->breadcrumbsData;
    }

    /**
     * Vrátí aktuální název cesty.
     *
     * @return string
     */
    public function getCurrentTitle(): string
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
    public function setCurrentTitleByRoute(string $route, string $variation = ''): self
    {
        $this->currentTitle = $this->getPageTitleForRoute($route, $variation);

        return $this;
    }

    /**
     * Manuálně nastaví aktuální title na zadaný string.
     *
     * @param string $currentTitle
     * @return $this
     */
    public function setCurrentTitle(string $currentTitle): self
    {
        $this->currentTitle = $currentTitle;

        return $this;
    }

    /**
     * Přidá string k aktuálnímu title.
     *
     * @param string $string
     * @return $this
     */
    public function appendToCurrentTitle(string $string): self
    {
        $this->currentTitle .= $string;

        return $this;
    }

    /**
     * Pro danou cestu vrátí její název. Také jde zadat variace.
     *
     * @param string $route
     * @param string $variation
     * @return string
     */
    private function getPageTitleForRoute(string $route, string $variation = ''): string
    {
        $key = 'app_page_title.' . $route;
        if (!$this->parameterBag->has($key))
        {
            return '';
        }

        $title = $this->parameterBag->get($key);
        if (is_array($title))
        {
            if (array_key_exists($variation, $title))
            {
                $title = $title[$variation];
            }
            else
            {
                return '';
            }
        }

        return (string) $title;
    }
}