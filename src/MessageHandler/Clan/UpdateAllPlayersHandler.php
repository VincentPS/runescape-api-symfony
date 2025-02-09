<?php

namespace App\MessageHandler\Clan;

use App\Message\Clan\UpdateAllPlayersMessage;
use App\Message\Clan\UpdateOnePlayerMessage;
use App\Repository\KnownPlayerRepository;
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
        $knownPlayers = $this->knownPlayerRepository->findBatchToUpdate();

        foreach ($knownPlayers as $knownPlayer) {
            $this->messageBus->dispatch(new UpdateOnePlayerMessage((string)$knownPlayer->getName()));
        }
    }
}
