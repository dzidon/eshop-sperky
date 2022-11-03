<?php

namespace App\TextContent;

use App\Entity\TextContent;
use LogicException;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

/**
 * Služba pro editor textového obsahu.
 *
 * @package App\TextContent
 */
class TextContentEditor
{
    private ParameterBagInterface $parameterBag;

    public function __construct(ParameterBagInterface $parameterBag)
    {
        $this->parameterBag = $parameterBag;
    }

    /**
     * Zjistí cestu, na které se vyskytuje specifická entita textového obsahu. Pokud žádná cesta není nalezena,
     * vrátí 'home' (domovská stránka).
     *
     * @param TextContent $textContent
     * @return string
     */
    public function getTextContentRoute(TextContent $textContent): string
    {
        if (!$this->parameterBag->has('app_text_content'))
        {
            throw new LogicException('Pro použití metody App\TextContent\TextContentEditor::getTextContentRoute je nutné, aby v services.yaml existoval parametr app_text_content.');
        }

        $configData = $this->parameterBag->get('app_text_content');
        if (!array_key_exists('route_preloading', $configData) || !is_array($configData['route_preloading']))
        {
            throw new LogicException('Pro použití metody App\TextContent\TextContentEditor::getTextContentRoute je nutné, aby v services.yaml existoval parametr app_text_content.route_preloading a aby obsahoval pole.');
        }

        foreach ($configData['route_preloading'] as $route => $textContentNames)
        {
            if ($route === '_all')
            {
                continue;
            }

            foreach ($textContentNames as $textContentName)
            {
                if ($textContentName === $textContent->getName())
                {
                    return $route;
                }
            }
        }

        return 'home';
    }
}