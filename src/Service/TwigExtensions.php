<?php

namespace App\Service;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class TwigExtensions extends AbstractExtension
{
    private ParameterBagInterface $params;

    public function __construct(ParameterBagInterface $params)
    {
        $this->params = $params;
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('get_parameter', [$this, 'getParameter']),
        ];
    }

    public function getParameter(string $name)
    {
        return $this->params->get($name);
    }
}