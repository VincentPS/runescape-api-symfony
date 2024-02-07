<?php

namespace App\Trait;

use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use Kevinrob\GuzzleCache\CacheMiddleware;
use Kevinrob\GuzzleCache\Storage\Psr6CacheStorage;
use Kevinrob\GuzzleCache\Strategy\GreedyCacheStrategy;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;

trait GuzzleCachedClientTrait
{
    protected Client $client;

    protected function getClient(): Client
    {
        if (!isset($this->client)) {
            $requestCacheFolderName = 'GuzzleFileCache';
            $cacheFolderPath = $this->getProjectDir() . '/var/cache';
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
                    new GreedyCacheStrategy($cache_storage, 600)
                ),
                'greedy-cache'
            );

            $this->client = new Client(['handler' => $stack]);
        }

        return $this->client;
    }

    private function getProjectDir(): ?string
    {
        $markerFiles = ['.env', 'composer.json'];
        $currentDir = realpath(getcwd());

        while ($currentDir !== '/') {
            foreach ($markerFiles as $marker) {
                $markerPath = $currentDir . DIRECTORY_SEPARATOR . $marker;

                if (file_exists($markerPath)) {
                    return $currentDir;
                }
            }

            $currentDir = dirname($currentDir);
        }

        return null;
    }
}
