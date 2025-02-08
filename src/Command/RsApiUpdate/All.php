<?php

namespace App\Command\RsApiUpdate;

use App\Repository\KnownPlayerRepository;
use App\Repository\PlayerRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsCommand(
    name: 'rsapi:update:all',
    description: 'Fetch the newest data for all known players and update the database',
)]
class All extends Command
{
    use HandleSinglePlayerTrait;

    public function __construct(
        private readonly MessageBusInterface $messageBus,
        private readonly PlayerRepository $playerRepository,
        private readonly KnownPlayerRepository $knownPlayerRepository,
        ?string $name = null
    ) {
        parent::__construct($name);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $knownPlayers = $this->knownPlayerRepository->findAll();

        foreach ($knownPlayers as $knownPlayer) {
            $io->info('Updating data for player: ' . $knownPlayer->getName());
            $this->handleSinglePlayer($io, (string)$knownPlayer->getName());
        }

        $io->success(sprintf('Data updated for %d known players.', count($knownPlayers)));
        return Command::SUCCESS;
    }
}
