<?php

namespace App\Service;

use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class BreadcrumbsService
{
    private array $breadcrumbsData = [];
    private string $currentTitle = '';

    private ParameterBagInterface $parameterBag;

    public function __construct(ParameterBagInterface $parameterBag)
    {
        $this->parameterBag = $parameterBag;
    }

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

    public function setPageTitleByRoute(string $route): self
    {
        $this->currentTitle = (string) $this->parameterBag->get('app_page_title.' . $route);

        return $this;
    }

    public function setPageTitle(string $currentTitle): self
    {
        $this->currentTitle = $currentTitle;

        return $this;
    }
}