<?php

declare(strict_types=1);

namespace App\Command\RsApiUpdate;

use App\Message\Clan\UpdateAllPlayerClanNamesMessage;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsCommand(name: 'rsapi:update_clan_all', description: 'Updates the clan data for all known players')]
class UpdateClanCommand extends Command
{
    public function __construct(
        private readonly MessageBusInterface $messageBus,
        ?string $name = null
    ) {
        parent::__construct($name);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $this->messageBus->dispatch(new UpdateAllPlayerClanNamesMessage());
        $io->success('Clan data update initiated for all known players.');

        return Command::SUCCESS;
    }
}
