<?php

namespace App\Command\RsApiUpdate;

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
class Singular extends Command
{
    use HandleSinglePlayerTrait;

    public function __construct(
        private readonly MessageBusInterface $messageBus,
        private readonly PlayerRepository $playerRepository,
        ?string $name = null
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

        $playerName = $input->getArgument('playerName');

        if (!is_string($playerName)) {
            $io->error('Please provide a player name as a string.');
            return Command::FAILURE;
        }

        return $this->handleSinglePlayer($io, $playerName);
    }
}
