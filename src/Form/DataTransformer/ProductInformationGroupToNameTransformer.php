<?php

namespace App\Form\DataTransformer;

use App\Entity\ProductInformationGroup;
use Symfony\Component\Form\DataTransformerInterface;

class ProductInformationGroupToNameTransformer implements DataTransformerInterface
{
    /**
     * Transformuje objekt (ProductInformationGroup) na název (string).
     *
     * @param ProductInformationGroup|null $group
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
     * Transformuje název (string) na objekt (ProductInformationGroup).
     *
     * @param string $name
     * @return ProductInformationGroup|null
     */
    public function reverseTransform($name): ?ProductInformationGroup
    {
        if (!$name)
        {
            return null;
        }

        $group = new ProductInformationGroup();
        $group->setName($name);

        return $group;
    }
}