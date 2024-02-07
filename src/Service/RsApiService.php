<?php

namespace App\Service;

use App\Entity\Player;
use App\Enum\KnownPlayers;
use App\Enum\QuestStatus;
use App\Exception\PlayerApi\PlayerApiDataConversionException;
use App\Message\HandleDataPointPersist;
use App\Trait\GuzzleCachedClientTrait;
use App\Trait\SerializerAwareTrait;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\RequestOptions;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Serializer\Exception\ExceptionInterface;

class RsApiService
{
    use GuzzleCachedClientTrait;
    use SerializerAwareTrait;

    public function __construct(
        private readonly MessageBusInterface $messageBus
    ) {
    }

    /**
     * @param string $player
     * @param int $amountOfActivityItems
     * @param bool $doUpdateCheck
     * @return Player
     * @throws GuzzleException
     * @throws PlayerApiDataConversionException
     * @throws ExceptionInterface
     */
    public function getProfile(string $player, int $amountOfActivityItems = 20, bool $doUpdateCheck = true): Player
    {
        $player = trim($player);

        if (empty($player)) {
            $player = KnownPlayers::VincentS->value;
        }

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

        $jsonBody = $response->getBody()->getContents();

        if (empty($jsonBody)) {
            throw new PlayerApiDataConversionException('No response from RuneScape API');
        }

        /**
         * @var array{
         *     magic: int,
         *     questsstarted: int,
         *     totalskill: int,
         *     questscomplete: int,
         *     questsnotstarted: int,
         *     totalxp: int,
         *     ranged: int,
         *     activities: array{
         *          date: string,
         *          details: string,
         *          text: string
         *     }[],
         *     skillvalues: array{
         *          level: int,
         *          xp: int,
         *          rank: int,
         *          id: int
         *     }[],
         *     name: string,
         *     rank: string,
         *     melee: int,
         *     combatlevel: int,
         *     loggedIn: string
         * }|array{error: string, loggedIn: string} $jsonDecoded
         */
        $jsonDecoded = $this->getSerializer()->decode($jsonBody, 'json');

        // this is a workaround for the fact that the API returns an integer for totalxp, but it can be larger than
        // PHP's max int
        if (array_key_exists('totalxp', $jsonDecoded)) {
            $jsonDecoded['totalxp'] = (string)$jsonDecoded['totalxp'];
        }

        if (array_key_exists('error', $jsonDecoded) && $jsonDecoded['error'] === 'NO_PROFILE') {
            throw new PlayerApiDataConversionException('No player found with name: ' . $player);
        }

        $jsonDecoded['quests'] = $this->getQuests($player);

        /** @var Player $playerInfo */
        $playerInfo = $this->getSerializer()->deserialize(
            $this->getSerializer()->encode($jsonDecoded, 'json'),
            Player::class,
            'json',
        );

        $questsCompleted = 0;
        $questsStarted = 0;
        $questsNotStarted = 0;

        array_filter(
            $jsonDecoded['quests'],
            function ($quest) use (&$questsCompleted, &$questsStarted, &$questsNotStarted) {
                return match ($quest['status']) {
                    QuestStatus::Completed->value => $questsCompleted++,
                    QuestStatus::Started->value => $questsStarted++,
                    QuestStatus::NotStarted->value => $questsNotStarted++,
                    default => null,
                };
            }
        );

        foreach ($playerInfo->getSkillValues() as $skillValue) {
            if ($skillValue->xp > 200000000) {
                foreach ($playerInfo->getSkillValues() as $skillValueToCorrect) {
                    $skillValueToCorrect->xp = $skillValueToCorrect->xp / 10;
                }

                break;
            }
        }

        $playerInfo
            ->setClan($this->getClanName($player))
            ->setQuestsCompleted($questsCompleted)
            ->setQuestsStarted($questsStarted)
            ->setQuestsNotStarted($questsNotStarted);

        if ($doUpdateCheck) {
            $this->messageBus->dispatch(new HandleDataPointPersist($playerInfo));
        }

        return $playerInfo;
    }

    /**
     * @return array{
     *      title: string,
     *      status: string,
     *      difficulty: string,
     *      members: bool,
     *      questPoints: int,
     *      userEligible: bool
     * }[]
     * @throws GuzzleException
     */
    public function getQuests(string $player): array
    {
        $response = $this->getClient()->request(
            'GET',
            'https://apps.runescape.com/runemetrics/quests',
            [
                RequestOptions::QUERY => [
                    'user' => $player
                ]
            ]
        );

        /**
         * @var array{
         *     quests: array{
         *          title: string,
         *          status: string,
         *          difficulty: string,
         *          members: bool,
         *          questPoints: int,
         *          userEligible: bool
         *     }[]
         * } $jsonDecoded
         */
        $jsonDecoded = $this->getSerializer()->decode($response->getBody()->getContents(), 'json');

        return $jsonDecoded['quests'];
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
