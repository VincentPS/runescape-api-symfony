<?php

namespace App\ValueObject\GrandExchange;

class CatalogueResponse
{
    /**
     * @param int $total
     * @param CatalogueItem[] $items
     */
    public function __construct(
        public int $total,
        public array $items
    ) {
    }

    /**
     * @return CatalogueItem[]
     */
    public function getItems(): array
    {
        return $this->items;
    }

    /**
     * @param CatalogueItem[] $items
     */
    public function setItems(array $items): void
    {
        $this->items = $items;
    }

    /**
     * @param CatalogueItem $item
     */
    public function addItem(CatalogueItem $item): void
    {
        $this->items[] = $item;
    }
}
