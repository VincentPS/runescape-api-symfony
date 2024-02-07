<?php

namespace App\ValueObject\GrandExchange;

class CatalogueResponseCollection
{
    /**
     * @param CatalogueResponseCategory[] $categories
     */
    public function __construct(
        public array $categories = []
    ) {
    }

    /**
     * @return CatalogueResponseCategory[]
     */
    public function getCategories(): array
    {
        return $this->categories;
    }

    /**
     * @param array $categories
     */
    public function setCategories(array $categories): void
    {
        $this->categories = $categories;
    }

    /**
     * @param CatalogueResponseCategory $category
     */
    public function addCategory(CatalogueResponseCategory $category): void
    {
        $this->categories[] = $category;
    }
}
