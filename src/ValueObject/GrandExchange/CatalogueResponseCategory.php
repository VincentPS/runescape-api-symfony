<?php

namespace App\ValueObject\GrandExchange;

use App\Enum\CatalogueCategory;

class CatalogueResponseCategory
{
    public function __construct(
        public CatalogueCategory $category,
        public CatalogueResponse $catalogueResponse,
    ) {
    }
}
