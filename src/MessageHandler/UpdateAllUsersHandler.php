<?php

namespace App\MessageHandler;

use App\Message\FetchLatestApiData;
use App\Message\UpdateAllUsers;
use App\Repository\KnownPlayerRepository;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsMessageHandler]
class UpdateAllUsersHandler
{
    public function __construct(
        private readonly MessageBusInterface $messageBus,
        private readonly KnownPlayerRepository $knownPlayerRepository
    ) {
    }


    public function __invoke(UpdateAllUsers $message): void
    {
        $knownPlayers = $this->knownPlayerRepository->findAll();

        foreach ($knownPlayers as $knownPlayer) {
            $this->messageBus->dispatch(new FetchLatestApiData((string)$knownPlayer->getName()));
        }
    }
}
