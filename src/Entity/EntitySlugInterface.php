<?php

namespace App\Entity;

/**
 * Interface pro entity, které mají mít slug.
 *
 * @package App\Entity
 */
interface EntitySlugInterface
{
    /**
     * Getter pro atribut slugu.
     *
     * @return string|null
     */
    public function getSlug(): ?string;

    /**
     * Setter pro atribut slugu.
     *
     * @param string|null $slug
     * @return $this
     */
    public function setSlug(?string $slug): self;

    /**
     * Array obsahující názvy atributů, jejichž hodnoty mají být ve slugu.
     *
     * @return array
     */
    public static function getAttributesForSlug(): array;
}