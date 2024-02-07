<?php

namespace App\Tests\Trait;

use PHPUnit\Framework\TestCase;
use GuzzleHttp\Client;
use App\Trait\GuzzleCachedClientTrait;

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
