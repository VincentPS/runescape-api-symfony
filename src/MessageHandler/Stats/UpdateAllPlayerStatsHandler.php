<?php

namespace App\MessageHandler\Stats;

use App\Enum\UpdateAllPlayersType;
use App\Message\Stats\UpdateAllPlayerStatsMessage;
use App\Message\Stats\UpdateSingularPlayerStatsMessage;
use App\Repository\KnownPlayerRepository;
use DateMalformedStringException;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsMessageHandler]
final readonly class UpdateAllPlayerStatsHandler
{
    public function __construct(
        private MessageBusInterface $messageBus,
        private KnownPlayerRepository $knownPlayerRepository
    ) {
    }


    public function __invoke(UpdateAllPlayerStatsMessage $message): void
    {
        try {
            $knownPlayers = match ($message->type) {
                UpdateAllPlayersType::ACTIVE => $this->knownPlayerRepository->findAllActive(),
                UpdateAllPlayersType::INACTIVE => $this->knownPlayerRepository->findAllInactive(),
            };
        } catch (DateMalformedStringException) {
            // Do nothing because this cannot happen unless the ACTIVITY_THRESHOLD is changed incorrectly
            return;
        }

        foreach ($knownPlayers as $knownPlayer) {
            $this->messageBus->dispatch(new UpdateSingularPlayerStatsMessage((string)$knownPlayer->getName()));
        }
    }
}
