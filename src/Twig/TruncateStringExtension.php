<?php

namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Umožňuje zkrátit string v Twigu.
 *
 * @package App\Twig
 */
class TruncateStringExtension extends AbstractExtension
{
    public function getFunctions(): array
    {
        return [
            new TwigFunction('truncate', [$this, 'truncate']),
        ];
    }

    public function truncate(string $string, int $maxCharacters, string $stringToAppend = '...'): string
    {
        if (mb_strlen($string, 'utf-8') > $maxCharacters)
        {
            return mb_substr($string, 0, $maxCharacters, 'utf-8') . $stringToAppend;
        }
        else
        {
            return $string;
        }
    }
}