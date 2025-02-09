<?php

namespace App\MessageHandler\Stats;

use App\Exception\PlayerApi\PlayerApiDataConversionException;
use App\Exception\PlayerApi\PlayerNotAMemberException;
use App\Exception\PlayerApi\PlayerNotFoundException;
use App\Message\Stats\UpdateOnePlayerMessage;
use App\Service\RsApiService;
use GuzzleHttp\Exception\GuzzleException;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\Exception\ExceptionInterface;

#[AsMessageHandler]
final readonly class UpdateOnePlayerHandler
{
    public function __construct(
        private RsApiService $rsApiService
    ) {
    }

    /**
     * @throws GuzzleException
     * @throws PlayerApiDataConversionException
     * @throws PlayerNotAMemberException
     * @throws PlayerNotFoundException
     * @throws ExceptionInterface
     */
    public function __invoke(UpdateOnePlayerMessage $message): void
    {
        $this->rsApiService->getProfile($message->player);
    }
}
