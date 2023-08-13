<?php

namespace App\MessageHandler;

use App\Message\HandleDataPointPersist;
use App\Repository\PlayerRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class HandleDataPointPersistHandler
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly PlayerRepository $playerRepository
    ) {
    }

    public function __invoke(HandleDataPointPersist $message): void
    {
        $player = $message->dataPoint;

        if (is_null($player->getName())) {
            return;
        }

        $latestDataPoint = $this->playerRepository->findLatestByName($player->getName());

        if (!is_null($latestDataPoint) && $latestDataPoint->getTotalXp() === $player->getTotalXp()) {
            return;
        }

        $this->entityManager->persist($player);
        $this->entityManager->flush();
    }
}
