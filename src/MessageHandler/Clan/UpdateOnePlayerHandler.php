<?php

namespace App\MessageHandler\Clan;

use App\Message\Clan\UpdateOnePlayerMessage;
use App\Repository\KnownPlayerRepository;
use App\Service\RsApiService;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use GuzzleHttp\Exception\GuzzleException;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class UpdateOnePlayerHandler
{
    public function __construct(
        private RsApiService $rsApiService,
        private KnownPlayerRepository $knownPlayerRepository,
        private EntityManagerInterface $entityManager
    ) {
    }

    public function __invoke(UpdateOnePlayerMessage $message): void
    {
        $knownPlayer = $this->knownPlayerRepository->findOneByName($message->player);

        if ($knownPlayer === null) {
            return;
        }

        try {
            $newClanName = $this->rsApiService->getClanName($message->player);

            if ($newClanName !== $knownPlayer->getClanName()) {
                $knownPlayer->setClanName($newClanName);
            }

            $knownPlayer->setUpdatedAt(new DateTimeImmutable());
            $this->entityManager->flush();
        } catch (GuzzleException) {
            // Ignore this exception as we can't do anything about it anyway. This player will be updated again on a
            // later run. This could as well be a rate limit from the API.
            return;
        }
    }
}
