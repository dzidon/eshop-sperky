<?php

namespace App\SlugGenerator;

class SlugGeneratorWithTime extends AbstractSlugGenerator
{
    /**
     * {@inheritdoc}
     */
    protected function getExtraDataForSlug(): string
    {
        return date("HisdmY");
    }
}