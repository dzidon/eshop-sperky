<?php

namespace App\Form\EventSubscriber\SlugGeneratorSubscriber;

use App\SlugGenerator\SlugGeneratorWithTime;

/**
 * Subscriber generující slug s časem ve formuláři.
 *
 * @package App\Form\EventSubscriber\SlugGeneratorSubscriber
 */
class SlugGeneratorWithTimeSubscriber extends AbstractSlugGeneratorSubscriber
{
    public function __construct(SlugGeneratorWithTime $slugGenerator)
    {
        parent::__construct($slugGenerator);
    }
}