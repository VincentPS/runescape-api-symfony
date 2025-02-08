<?php

namespace App\MessageHandler;

use App\Entity\KnownPlayer;
use App\Message\HandleDataPointPersist;
use App\Repository\KnownPlayerRepository;
use App\Repository\PlayerRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class HandleDataPointPersistHandler
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private PlayerRepository $playerRepository,
        private KnownPlayerRepository $knownPlayerRepository
    ) {
    }

    public function __invoke(HandleDataPointPersist $message): void
    {
        $player = $message->dataPoint;

        if (is_null($player->getName())) {
            return;
        }

        $knownPlayer = $this->knownPlayerRepository->findOneByName($player->getName());

        if ($knownPlayer === null) {
            $newKnownPlayer = new KnownPlayer();
            $newKnownPlayer->setName($player->getName());

            $this->entityManager->persist($newKnownPlayer);
            $this->entityManager->flush();
        }

        $latestDataPoint = $this->playerRepository->findLatestByName($player->getName());

        if (!is_null($latestDataPoint) && $latestDataPoint->getTotalXp() === $player->getTotalXp()) {
            return;
        }

        $this->entityManager->persist($player);
        $this->entityManager->flush();
    }
}
