<?php

namespace App\Form\DataTransformer;

use App\Entity\ProductCategory;
use Symfony\Component\Form\DataTransformerInterface;

class ProductCategoryToNameTransformer implements DataTransformerInterface
{

    /**
     * Transformuje objekt (ProductCategory) na název (string).
     *
     * @param ProductCategory|null $category
     * @return string
     */
    public function transform($category): string
    {
        if ($category === null)
        {
            return '';
        }

        $name = $category->getName();
        if($name === null)
        {
            return '';
        }

        return $name;
    }

    /**
     * Transformuje název (string) na objekt (ProductCategory).
     *
     * @param string $name
     * @return ProductCategory|null
     */
    public function reverseTransform($name): ?ProductCategory
    {
        if (!$name)
        {
            return null;
        }

        $category = new ProductCategory();
        $category->setName($name);

        return $category;
    }
}