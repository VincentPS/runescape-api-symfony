<?php

namespace App\Service;

use App\Entity\Player;
use App\Enum\KnownPlayers;
use App\Enum\QuestStatus;
use App\Exception\PlayerApi\PlayerApiDataConversionException;
use App\Message\HandleDataPointPersist;
use Doctrine\Common\Annotations\AnnotationReader;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\RequestOptions;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\PropertyInfo\Extractor\ReflectionExtractor;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Component\Serializer\Mapping\Factory\ClassMetadataFactory;
use Symfony\Component\Serializer\Mapping\Loader\AnnotationLoader;
use Symfony\Component\Serializer\Normalizer\ArrayDenormalizer;
use Symfony\Component\Serializer\Normalizer\BackedEnumNormalizer;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;

class RsApiService
{
    private Serializer $serializer;
    private Client $client;

    public function __construct(
        GuzzleCachedClient $client,
        private readonly MessageBusInterface $messageBus
    ) {
        $classMetadataFactory = new ClassMetadataFactory(
            new AnnotationLoader(
                new AnnotationReader()
            )
        );

        $this->serializer = new Serializer(
            [
                new DateTimeNormalizer(),
                new ArrayDenormalizer(),
                new BackedEnumNormalizer(),
                new ObjectNormalizer(
                    classMetadataFactory: $classMetadataFactory,
                    propertyTypeExtractor: new ReflectionExtractor()
                )
            ],
            [
                new JsonEncoder()
            ]
        );

        $this->client = $client->new();
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

        $response = $this->client->request(
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
        $jsonDecoded = $this->serializer->decode($jsonBody, 'json');

        if (array_key_exists('error', $jsonDecoded) && $jsonDecoded['error'] === 'NO_PROFILE') {
            throw new PlayerApiDataConversionException('No player found with name: ' . $player);
        }

        $jsonDecoded['quests'] = $this->getQuests($player);

        /** @var Player $playerInfo */
        $playerInfo = $this->serializer->deserialize(
            $this->serializer->encode($jsonDecoded, 'json'),
            Player::class,
            'json',
        );

        $questsCompleted = 0;
        $questsStarted = 0;
        $questsNotStarted = 0;

        array_filter(
            $jsonDecoded['quests'],
            function ($quest) use (&$questsCompleted, &$questsStarted, &$questsNotStarted) {
                match ($quest['status']) {
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
        $response = $this->client->request(
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
        $jsonDecoded = $this->serializer->decode($response->getBody()->getContents(), 'json');

        return $jsonDecoded['quests'];
    }

    /**
     * @throws GuzzleException
     */
    public function getClanName(string $player): string
    {
        $playerDetails = [];

        $response = $this->client->request(
            'GET',
            'https://services.runescape.com/m=website-data/playerDetails.ws',
            [
                RequestOptions::QUERY => [
                    'membership' => true,
                    'names' => $this->serializer->encode([$player], 'json'),
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
            $playerDetailsFromArray = $this->serializer->decode($playerDetails[1], 'json');
            $playerDetails = $playerDetailsFromArray[0];

            if (array_key_exists('clan', $playerDetails)) {
                return $playerDetails['clan'];
            }
        }

        return '';
    }
}
