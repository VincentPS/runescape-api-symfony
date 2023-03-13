<?php

namespace App\Service;

use App\Entity\Player;
use App\Enum\KnownPlayers;
use App\Enum\QuestStatus;
use App\Message\HandleDataPointPersist;
use Doctrine\Common\Annotations\AnnotationReader;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\RequestOptions;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\PropertyInfo\Extractor\ReflectionExtractor;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
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
        $classMetadataFactory = new ClassMetadataFactory(new AnnotationLoader(new AnnotationReader()));

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
        $jsonDecoded = $this->serializer->decode($jsonBody, 'json');
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
            if ($skillValue->getXp() > 200000000) {
                foreach ($playerInfo->getSkillValues() as $skillValueToCorrect) {
                    $skillValueToCorrect->setXp($skillValueToCorrect->getXp() / 10);
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

        return $this->serializer->decode($response->getBody()->getContents(), 'json')['quests'];
    }

    /**
     * @throws GuzzleException
     */
    public function getClanName(string $player): string
    {
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

        preg_match(
            '/angular\.callbacks\._0\((.*?)\)/',
            $response->getBody()->getContents(),
            $playerDetails
        );

        if (!empty($playerDetails) && array_key_exists(1, $playerDetails)) {
            $playerDetails = $this->serializer->decode($playerDetails[1], 'json')[0];

            if (array_key_exists('clan', $playerDetails)) {
                return $playerDetails['clan'];
            }
        }

        return '';
    }
}
