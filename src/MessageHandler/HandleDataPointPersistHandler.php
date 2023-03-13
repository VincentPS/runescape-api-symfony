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
        $doPersist = true;
        $player = $message->dataPoint;
        $latestDataPoint = $this->playerRepository->findLatestByName($player->getName());

        if (!is_null($latestDataPoint)) {
            if (
//                $latestDataPoint->getActivities() == $player->getActivities()
//                && $latestDataPoint->getQuests() == $player->getQuests()
                $latestDataPoint->getTotalXp() === $player->getTotalXp()
            ) {
                $doPersist = false;
            }
        }

        if ($doPersist) {
            $this->entityManager->persist($player);
            $this->entityManager->flush();
        }
    }
}
