<?php

namespace App\Form\DataTransformer;

use App\Entity\ProductCategoryGroup;
use Symfony\Component\Form\DataTransformerInterface;

class ProductCategoryGroupToNameTransformer implements DataTransformerInterface
{

    /**
     * Transformuje objekt (ProductCategoryGroup) na název (string).
     *
     * @param ProductCategoryGroup|null $group
     * @return string
     */
    public function transform($group): string
    {
        if ($group === null)
        {
            return '';
        }

        $name = $group->getName();
        if($name === null)
        {
            return '';
        }

        return $name;
    }

    /**
     * Transformuje název (string) na objekt (ProductCategoryGroup).
     *
     * @param string $name
     * @return ProductCategoryGroup|null
     */
    public function reverseTransform($name): ?ProductCategoryGroup
    {
        if (!$name)
        {
            return null;
        }

        $group = new ProductCategoryGroup();
        $group->setName($name);

        return $group;
    }
}