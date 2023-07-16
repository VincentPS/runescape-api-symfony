<?php

namespace App\Dto;

use DateTimeImmutable;

class Activity implements JsonbDTOInterface
{
    public ?DateTimeImmutable $date = null;
    public ?string $details = null;
    public ?string $text = null;
}
