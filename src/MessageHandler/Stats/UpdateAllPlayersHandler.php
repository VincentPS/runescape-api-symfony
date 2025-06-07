<?php

namespace App\MessageHandler\Stats;

use App\Enum\UpdateAllPlayersType;
use App\Message\Stats\UpdateAllPlayersMessage;
use App\Message\Stats\UpdateOnePlayerMessage;
use App\Repository\KnownPlayerRepository;
use DateMalformedStringException;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsMessageHandler]
final readonly class UpdateAllPlayersHandler
{
    public function __construct(
        private MessageBusInterface $messageBus,
        private KnownPlayerRepository $knownPlayerRepository
    ) {
    }


    public function __invoke(UpdateAllPlayersMessage $message): void
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
            $this->messageBus->dispatch(new UpdateOnePlayerMessage((string)$knownPlayer->getName()));
        }
    }
}
