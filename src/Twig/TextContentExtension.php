<?php

namespace App\Twig;

use App\Service\TextContentLoader;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Twig extension pro vykreslení editovatelného textového obsahu.
 *
 * @package App\Twig
 */
class TextContentExtension extends AbstractExtension
{
    private TextContentLoader $textContentLoader;

    public function __construct(TextContentLoader $textContentLoader)
    {
        $this->textContentLoader = $textContentLoader;
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('get_text_content', [$this->textContentLoader, 'getTextContent']),
        ];
    }
}