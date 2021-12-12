<?php

namespace App\Service;

use App\Entity\Address;
use App\Entity\Review;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

/**
 * Třída TwigExtensions přidává rozšířující metody a filtry šablonovacímu systému Twig.
 *
 * @package App\Service
 */
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
            new TwigFunction('rating_to_stars', [$this, 'ratingToStars']),
            new TwigFunction('get_country_name', [Address::class, 'getCountryNameByCode']),
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

    /**
     * Vezme hodnocení a převede ho na data, podle kterých jde zobrazit hvězdy v šabloně
     *
     * @param float $rating
     * @return array
     */
    public function ratingToStars(float $rating): array
    {
        $fullStars = (int) floor($rating);
        $fullStarsAndHalfStar = (int) round($rating);

        $starData = [];
        for($star = 1; $star <= Review::STAR_COUNT; $star++)
        {
            if($star <= $fullStars)
            {
                $starData[] = 'full';
            }
            else if($star === $fullStarsAndHalfStar)
            {
                $starData[] = 'half';
            }
            else
            {
                $starData[] = 'empty';
            }
        }

        return $starData;
    }
}