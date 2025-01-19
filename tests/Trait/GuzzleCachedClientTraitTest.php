<?php

namespace App\Tests\Trait;

use App\Trait\GuzzleCachedClientTrait;
use GuzzleHttp\Client;
use PHPUnit\Framework\TestCase;

class GuzzleCachedClientTraitTest extends TestCase
{
    use GuzzleCachedClientTrait;

    public function testGetClientReturnsClientInstance(): void
    {
        $client = $this->getClient();
        $this->assertInstanceOf(Client::class, $client);
    }

    public function testGetClientReturnsSameInstance(): void
    {
        $client1 = $this->getClient();
        $client2 = $this->getClient();

        $this->assertSame($client1, $client2);
    }
}
