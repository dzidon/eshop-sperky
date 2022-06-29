<?php

namespace App\SlugGenerator;

use App\Entity\EntitySlugInterface;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\String\Slugger\SluggerInterface;

/**
 * Abstraktní třída pro všechny generátory slugů.
 *
 * @package App\SlugGenerator
 */
abstract class AbstractSlugGenerator
{
    protected SluggerInterface $slugger;
    protected PropertyAccessorInterface $propertyAccessor;

    public function __construct(SluggerInterface $slugger, PropertyAccessorInterface $propertyAccessor)
    {
        $this->slugger = $slugger;
        $this->propertyAccessor = $propertyAccessor;
    }

    /**
     * Řeší automatické vygenerování slugu.
     *
     * @param EntitySlugInterface $entity
     * @return string
     */
    public function generateAutomatically(EntitySlugInterface $entity): string
    {
        $stringToConvert = '';

        // Data z instance
        foreach ($entity->getAttributesForSlug() as $attribute)
        {
            $stringToConvert .= $this->propertyAccessor->getValue($entity, $attribute) . ' ';
        }

        // Extra data
        $stringToConvert .= $this->getExtraDataForSlug();

        // Převedení stringu na slug
        return $this->generateFromString($stringToConvert);
    }

    /**
     * Vygeneruje slug ze stringu a všechna písmena převede na malá.
     *
     * @param string $stringToConvert
     * @return string
     */
    public function generateFromString(string $stringToConvert): string
    {
        return strtolower($this->slugger->slug($stringToConvert));
    }

    /**
     * Specifikuje string, který se má vložit do slugu pro unikátnost.
     *
     * @return string
     */
    abstract protected function getExtraDataForSlug(): string;
}