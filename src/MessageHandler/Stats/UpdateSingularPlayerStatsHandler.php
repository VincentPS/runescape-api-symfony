<?php

namespace App\MessageHandler\Stats;

use App\Exception\PlayerApi\PlayerApiDataConversionException;
use App\Exception\PlayerApi\PlayerNotAMemberException;
use App\Exception\PlayerApi\PlayerNotFoundException;
use App\Message\Stats\UpdateSingularPlayerStatsMessage;
use App\Repository\KnownPlayerRepository;
use App\Service\RsApiService;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use GuzzleHttp\Exception\GuzzleException;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class UpdateSingularPlayerStatsHandler
{
    public function __construct(
        private RsApiService $rsApiService,
        private KnownPlayerRepository $knownPlayerRepository,
        private EntityManagerInterface $entityManager
    ) {
    }

    /**
     * @throws GuzzleException
     */
    public function __invoke(UpdateSingularPlayerStatsMessage $message): void
    {
        try {
            $this->rsApiService->getProfile($message->player);

            $knownPlayer = $this->knownPlayerRepository->findOneByName($message->player);
            if ($knownPlayer !== null) {
                $knownPlayer->setUpdatedAt(new DateTimeImmutable());
                $this->entityManager->flush();
            }
        } catch (PlayerNotFoundException | PlayerNotAMemberException | PlayerApiDataConversionException $e) {
            if ($e instanceof PlayerApiDataConversionException) {
                return; // Ignore this exception, it could mean the API temporarily returned an error
            }

            // check if player is a KnownPlayer and remove it because it can't be found anymore
            $knownPlayer = $this->knownPlayerRepository->findOneByName($message->player);

            if ($knownPlayer !== null) {
                $this->entityManager->remove($knownPlayer);
                $this->entityManager->flush();
            }
        }
    }
}
