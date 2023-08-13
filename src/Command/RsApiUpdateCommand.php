<?php

namespace App\Command;

use App\Message\FetchLatestApiData;
use App\Repository\PlayerRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsCommand(
    name: 'rsapi:update',
    description: 'Fetch the newest data for a player and update the database',
)]
class RsApiUpdateCommand extends Command
{
    public function __construct(
        private readonly MessageBusInterface $messageBus,
        private readonly PlayerRepository $playerRepository,
        string $name = null
    ) {
        parent::__construct($name);
    }

    protected function configure(): void
    {
        $this
            ->addArgument('playerName', InputArgument::REQUIRED, 'Player to be updated');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $playerName = strval($input->getArgument('playerName'));

        if (empty($playerName)) {
            $io->error('Please provide a player name.');
        }

        $latestDataPointBeforeUpdate = $this->playerRepository->findLatestByName($playerName);

        $io->info('Data will be updated for player: ' . $playerName);
        $this->messageBus->dispatch(new FetchLatestApiData($playerName));

        $latestDataPointAfterUpdate = $this->playerRepository->findLatestByName($playerName);

        if (is_null($latestDataPointBeforeUpdate) || is_null($latestDataPointAfterUpdate)) {
            $io->error([
                'No data was found for player: ' . $playerName,
                'Please check the spelling of the player name.',
            ]);

            return Command::FAILURE;
        }

        if ($latestDataPointBeforeUpdate->getCreatedAt() === $latestDataPointAfterUpdate->getCreatedAt()) {
            $io->info([
                'No new data was found',
                'Latest data point was created at: ' .
                $latestDataPointBeforeUpdate
                    ->getCreatedAt()
                    ->format('D, d M Y H:i:s'),
            ]);

            return Command::SUCCESS;
        }

        $io->success([
            'Data updated for player: ' . $playerName,
            'Latest data point now at: ' .
            $latestDataPointAfterUpdate
                ->getCreatedAt()
                ->format('D, d M Y H:i:s'),
        ]);

        return Command::SUCCESS;
    }
}
