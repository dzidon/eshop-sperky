<?php

namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

/**
 * Twig extension přidaávájící funkce související s ParameterBag
 *
 * @package App\Twig
 */
class ParameterBagExtension extends AbstractExtension
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

    /**
     * Vrátí hodnotu parametru z services.yaml bez nutnosti vytvářet Twig global.
     *
     * @param string $name
     *
     * @return array|bool|float|int|string|null
     */
    public function getParameter(string $name)
    {
        return $this->params->get($name);
    }
}