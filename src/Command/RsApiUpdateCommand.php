<?php

namespace App\Command;

use App\Message\FetchLatestApiData;
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
    public function __construct(private readonly MessageBusInterface $messageBus, string $name = null)
    {
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

        if (empty($playerName)) {
            $io->error('You did not provide a player name.');
        }

        $io->success("Data will be updated for player: $playerName");
        $this->messageBus->dispatch(new FetchLatestApiData($playerName));

        return Command::SUCCESS;
    }
}
