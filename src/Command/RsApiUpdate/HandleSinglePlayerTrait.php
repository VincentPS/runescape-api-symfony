<?php

namespace App\Command\RsApiUpdate;

use App\Message\Stats\UpdateOnePlayerMessage;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;

trait HandleSinglePlayerTrait
{
    public function handleSinglePlayer(SymfonyStyle $io, string $playerName): int
    {
        if (empty($playerName)) {
            $io->error('Please provide a player name.');
        }

        $latestDataPointBeforeUpdate = $this->playerRepository->findLatestByName($playerName);

        $io->info('Data will be updated for player: ' . $playerName);
        $this->messageBus->dispatch(new UpdateOnePlayerMessage($playerName));

        $latestDataPointAfterUpdate = $this->playerRepository->findLatestByName($playerName);

        if (is_null($latestDataPointBeforeUpdate)) {
            $io->info([
                'No data was found for player: ' . $playerName . ' yet.',
                'Attempting to fetch data from the API.',
            ]);
        }

        if (is_null($latestDataPointAfterUpdate)) {
            $io->error([
                'No data was found for player: ' . $playerName . ' after attempting to fetch data from the API.',
                'Please check the logs for more information.',
            ]);

            return Command::FAILURE;
        }

        if ($latestDataPointBeforeUpdate?->getCreatedAt() === $latestDataPointAfterUpdate->getCreatedAt()) {
            $io->info([
                'No new data was found',
                'Latest data point was created at: ' .
                $latestDataPointBeforeUpdate?->getCreatedAt()?->format('D, d M Y H:i:s'),
            ]);

            return Command::SUCCESS;
        }

        $io->success([
            'Data updated for player: ' . $playerName,
            'Latest data point now at: ' .
            $latestDataPointAfterUpdate->getCreatedAt()?->format('D, d M Y H:i:s'),
        ]);

        return Command::SUCCESS;
    }
}
