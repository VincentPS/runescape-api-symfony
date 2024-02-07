<?php

namespace App\ValueObject\GrandExchange;

class CataloguePriceDetails
{
    public function __construct(
        public string $trend,
        public string | int $price,
    ) {
    }
}
