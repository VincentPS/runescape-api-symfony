<?php

namespace App\ValueObject\GrandExchange;

class CatalogueItem
{
    public function __construct(
        public string $icon,
        public string $icon_large,
        public int $id,
        public string $type,
        public string $typeIcon,
        public string $name,
        public string $description,
        public CataloguePriceDetails $current,
        public CataloguePriceDetails $today,
        public string $members,
    ) {
    }
}
