<?php

namespace App\MessageHandler\Clan;

use App\Message\Clan\UpdateAllPlayerClanNamesMessage;
use App\Repository\KnownPlayerRepository;
use App\Service\RsApiService;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class UpdateAllPlayerClanNamesHandler
{
    public function __construct(
        private RsApiService $rsApiService,
        private KnownPlayerRepository $knownPlayerRepository
    ) {
    }

    public function __invoke(UpdateAllPlayerClanNamesMessage $message): void
    {
        $knownPlayers = $this->knownPlayerRepository->findAllNames();
        $players = $this->rsApiService->getClanNames($knownPlayers);

        foreach ($players as $player) {
            $this->knownPlayerRepository->updateClanName($player['name'], $player['clan']);
        }
    }
}
