<?php

namespace App\Command;

use App\Repository\PlayerRepository;
use App\Service\XpBoundaryService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'db:verify-player-data-integrity',
    description: 'Verify the integrity of player data'
)]
class VerifyPlayerDataIntegrityCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly PlayerRepository $playerRepository,
        private readonly XpBoundaryService $xpBoundaryService,
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

        /** @var string $playerName */
        $playerName = $input->getArgument('playerName');

        if (empty($playerName) || !is_string($playerName)) {
            $io->error('Please provide a player name.');
        }

        $io->info('Data will be verified for player: ' . $playerName);

        $dataPoints = $this->playerRepository->findAllByName($playerName);

        foreach ($dataPoints as $dataPoint) {
            foreach ($skillValues = $dataPoint->getSkillValues() as $skillValue) {
                if ($skillValue->id === null) {
                    $io->info('Skill was not found, removing data point.');
                    $this->playerRepository->remove($dataPoint);
                    continue;
                }

                $isXpWithinBounds = $this->xpBoundaryService->isXpWithinLevelBoundaries(
                    $skillValue->id,
                    (int)$skillValue->level,
                    (float)$skillValue->xp
                );

                if (!$isXpWithinBounds && $skillValue->level !== 99) {
                    $io->info(sprintf(
                        'Datapoint from %s contains skill %s with level %d and xp %d is not within the ' .
                        'boundaries, correcting skill xp.',
                        $dataPoint->getCreatedAt()?->format('D, d M Y H:i:s') ?? 'unknown',
                        $skillValue->id->value,
                        (int)$skillValue->level,
                        (float)$skillValue->xp
                    ));
                    $skillValue->xp /= 10;
                }
            }

            $dataPoint->setSkillValues($skillValues);
        }

        $this->entityManager->flush();
        $io->success('Player data has been verified for player: ' . $playerName);

        return Command::SUCCESS;
    }
}
