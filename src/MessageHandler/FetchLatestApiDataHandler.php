<?php

namespace App\MessageHandler;

use App\Message\FetchLatestApiData;
use App\Service\RsApiService;
use GuzzleHttp\Exception\GuzzleException;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

#[AsMessageHandler]
final class FetchLatestApiDataHandler
{
    public function __construct(private readonly RsApiService $rsApiService)
    {
    }

    /**
     * @throws GuzzleException
     */
    public function __invoke(FetchLatestApiData $message): void
    {
        $this->rsApiService->getProfile($message->playerName);
    }
}
