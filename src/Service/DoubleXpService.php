<?php

namespace App\Service;

use App\Trait\GuzzleCachedClientTrait;
use GuzzleHttp\Exception\GuzzleException;

class DoubleXpService
{
    use GuzzleCachedClientTrait;

    public function isDoubleXpLive(): bool
    {
        try {
            $response = $this->getClient()->get('https://www.runescape.com/community/double-xp-live');
            $content = $response->getBody()->getContents();

            return str_contains($content, 'Currently active') && str_contains($content, 'Ends on');
        } catch (GuzzleException) {
            return false;
        }
    }
}
