<?php

namespace App\Twig;

use App\Entity\Review;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Twig extension přidaávájící funkce související s recenzí
 *
 * @package App\Twig
 */
class ReviewExtension extends AbstractExtension
{
    public function getFunctions(): array
    {
        return [
            new TwigFunction('rating_to_stars', [$this, 'ratingToStars']),
        ];
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