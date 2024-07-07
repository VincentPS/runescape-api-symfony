<?php

namespace App\Service;

use App\Entity\Player;
use App\Enum\QuestStatus;
use App\Exception\PlayerApi\PlayerApiDataConversionException;
use App\Message\HandleDataPointPersist;
use App\Trait\GuzzleCachedClientTrait;
use App\Trait\SerializerAwareTrait;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\RequestOptions;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;

class RsApiService
{
    use GuzzleCachedClientTrait;
    use SerializerAwareTrait;

    public function __construct(
        private readonly MessageBusInterface $messageBus,
        private readonly XpBoundaryService $xpBoundaryService,
    ) {
    }

    /**
     * @throws GuzzleException
     * @throws PlayerApiDataConversionException
     * @throws ExceptionInterface
     */
    public function getProfile(string $player, int $amountOfActivityItems = 20): Player
    {
        $playerJsonResponse = $this
            ->getProfileJsonResponse($player, $amountOfActivityItems);

        $questJsonResponse = $this
            ->getQuestsJsonResponse($player);

        $playerAndQuestsJsonString = sprintf(
            '%s,%s',
            rtrim($playerJsonResponse, '}'),
            substr($questJsonResponse, 1)
        );

        /** @var Player $playerInfo */
        $playerInfo = $this
            ->getSerializer()
            ->deserialize(
                $playerAndQuestsJsonString,
                Player::class,
                'json',
                [AbstractObjectNormalizer::DISABLE_TYPE_ENFORCEMENT => true]
            );

        $questsCompleted = 0;
        $questsStarted = 0;
        $questsNotStarted = 0;

        array_filter(
            $playerInfo->getQuests(),
            function ($quest) use (&$questsCompleted, &$questsStarted, &$questsNotStarted) {
                return match ($quest->status) {
                    QuestStatus::Completed => $questsCompleted++,
                    QuestStatus::Started => $questsStarted++,
                    QuestStatus::NotStarted => $questsNotStarted++,
                    default => null,
                };
            }
        );

        foreach ($skillValues = $playerInfo->getSkillValues() as $skillValue) {
            if ($skillValue->id === null) {
                continue;
            }

            $xpWithinBoundary = $this->xpBoundaryService->isXpWithinLevelBoundaries(
                $skillValue->id,
                (int)$skillValue->level,
                (float)$skillValue->xp
            );

            if ($xpWithinBoundary === false) {
                $skillValue->xp /= 10;
            }
        }

        $playerInfo
            ->setSkillValues($skillValues)
            ->setClan($this->getClanName($player))
            ->setQuestsCompleted($questsCompleted)
            ->setQuestsStarted($questsStarted)
            ->setQuestsNotStarted($questsNotStarted);

        $this->messageBus
            ->dispatch(new HandleDataPointPersist($playerInfo));

        return $playerInfo;
    }

    /**
     * @throws GuzzleException
     * @throws PlayerApiDataConversionException
     */
    private function getProfileJsonResponse(string $player, int $amountOfActivityItems = 20): string
    {
        $response = $this->getClient()->request(
            'GET',
            'https://apps.runescape.com/runemetrics/profile/profile',
            [
                RequestOptions::QUERY => [
                    'user' => $player,
                    'activities' => $amountOfActivityItems,
                    'time' => time()
                ]
            ]
        );

        $jsonResponse = $response
            ->getBody()
            ->getContents();

        if (empty($jsonResponse)) {
            throw new PlayerApiDataConversionException('No response from RuneScape API');
        }

        if (str_contains($jsonResponse, 'NO_PROFILE')) {
            throw new PlayerApiDataConversionException('No player found with name: ' . $player);
        }

        return $jsonResponse;
    }

    /**
     * @throws GuzzleException
     * @throws PlayerApiDataConversionException
     */
    private function getQuestsJsonResponse(string $player): string
    {
        $response = $this
            ->getClient()
            ->request(
                'GET',
                'https://apps.runescape.com/runemetrics/quests',
                [
                    RequestOptions::QUERY => [
                        'user' => $player
                    ]
                ]
            );

        $jsonResponse = $response
            ->getBody()
            ->getContents();

        if (str_contains($jsonResponse, '"quests": [],')) {
            throw new PlayerApiDataConversionException('No quests found for player: ' . $player);
        }

        return $jsonResponse;
    }

    /**
     * @throws GuzzleException
     */
    public function getClanName(string $player): string
    {
        $playerDetails = [];

        $response = $this->getClient()->request(
            'GET',
            'https://services.runescape.com/m=website-data/playerDetails.ws',
            [
                RequestOptions::QUERY => [
                    'membership' => true,
                    'names' => $this->getSerializer()->encode([$player], 'json'),
                    'callback' => 'angular.callbacks._0'
                ]
            ]
        );

        /**
         * @var array<int, array{
         *     isSuffix: bool,
         *     recruiting: bool,
         *     name: string,
         *     clan: string,
         *     title: string
         * }> $playerDetails
         */

        preg_match(
            '/angular\.callbacks\._0\((.*?)\)/',
            $response->getBody()->getContents(),
            $playerDetails
        );

        if (!empty($playerDetails) && array_key_exists(1, $playerDetails)) {
            /** @var array<int, array{
             *     isSuffix: bool,
             *     recruiting: bool,
             *     name: string,
             *     clan: string,
             *     title: string
             * }> $playerDetailsFromArray
             */
            $playerDetailsFromArray = $this->getSerializer()->decode($playerDetails[1], 'json');
            $playerDetails = $playerDetailsFromArray[0];

            if (array_key_exists('clan', $playerDetails)) {
                return $playerDetails['clan'];
            }
        }

        return '';
    }
}
