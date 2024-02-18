<?php

namespace App\MessageHandler;

use App\Exception\PlayerApi\PlayerApiDataConversionException;
use App\Message\FetchLatestApiData;
use App\Service\RsApiService;
use GuzzleHttp\Exception\GuzzleException;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Serializer\Exception\ExceptionInterface;

#[AsMessageHandler]
final readonly class FetchLatestApiDataHandler
{
    public function __construct(private RsApiService $rsApiService)
    {
    }

    /**
     * @param FetchLatestApiData $message
     * @throws GuzzleException
     * @throws PlayerApiDataConversionException
     * @throws ExceptionInterface
     */
    public function __invoke(FetchLatestApiData $message): void
    {
        $this->rsApiService->getProfile($message->playerName);
    }
}
