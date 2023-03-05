<?php

namespace App\Message;

final class FetchLatestApiData implements AsyncEventInterface
{
    public function __construct(public string $playerName)
    {
    }
}
