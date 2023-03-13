<?php

namespace App\Service;

use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use Kevinrob\GuzzleCache\CacheMiddleware;
use Kevinrob\GuzzleCache\Storage\Psr6CacheStorage;
use Kevinrob\GuzzleCache\Strategy\GreedyCacheStrategy;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;

class GuzzleCachedClient
{
    public function __construct(private readonly string $projectDir)
    {
    }

    public function new(): Client
    {
        $requestCacheFolderName = 'GuzzleFileCache';
        $cacheFolderPath = "$this->projectDir/var/cache";
        $cache_storage = new Psr6CacheStorage(
            new FilesystemAdapter(
                $requestCacheFolderName,
                600,
                $cacheFolderPath
            )
        );

        $stack = HandlerStack::create();
        $stack->push(
            new CacheMiddleware(
                new GreedyCacheStrategy(
                    $cache_storage,
                    600
                )
            ),
            'greedy-cache'
        );

        return new Client(['handler' => $stack]);
    }
}
