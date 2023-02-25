<?php

namespace App\Service;

use App\Dto\PlayerInfo;
use App\Dto\QuestResponse;
use App\Enum\QuestStatus;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\RequestOptions;
use Symfony\Component\PropertyInfo\Extractor\ReflectionExtractor;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\ArrayDenormalizer;
use Symfony\Component\Serializer\Normalizer\BackedEnumNormalizer;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;

class RsApiService
{
    private Serializer $serializer;

    public function __construct()
    {
        $this->serializer = new Serializer(
            [
                new DateTimeNormalizer(),
                new ArrayDenormalizer(),
                new BackedEnumNormalizer(),
                new ObjectNormalizer(propertyTypeExtractor: new ReflectionExtractor())
            ],
            [
                new JsonEncoder()
            ]
        );
    }

    /**
     * @param string $player
     * @param int $amountOfActivityItems
     * @return PlayerInfo
     * @throws GuzzleException
     */
    public function getProfile(string $player, int $amountOfActivityItems = 5): PlayerInfo
    {
        $client = new Client();

        $response = $client->request(
            'GET',
            'https://apps.runescape.com/runemetrics/profile/profile',
            [
                RequestOptions::QUERY => [
                    'user' => $player,
                    'activities' => $amountOfActivityItems,
//                     't' => time()
                ]
            ]
        );

        /** @var PlayerInfo $playerInfo */
        $playerInfo = $this->serializer->deserialize(
            $response->getBody()->getContents(),
            PlayerInfo::class,
            'json'
        );

        $quests = $this->getQuests($player);
        $questsCompleted = 0;
        $questsStarted = 0;
        $questsNotStarted = 0;

        array_filter($quests->getQuests(),
            function ($quest) use (&$questsCompleted, &$questsStarted, &$questsNotStarted) {
                match ($quest->getStatus()) {
                    QuestStatus::Completed => $questsCompleted++,
                    QuestStatus::Started => $questsStarted++,
                    QuestStatus::NotStarted => $questsNotStarted++
                };
            });

        $skills = $playerInfo->getSkillValues();

        usort($skills, function ($a, $b) {
            return $a->getId()->value - $b->getId()->value;
        });

        $playerInfo
            ->setSkillValues($skills)
            ->setClan($this->getClanName($player))
            ->setQuests($quests->getQuests())
            ->setQuestsCompleted($questsCompleted)
            ->setQuestsStarted($questsStarted)
            ->setQuestsNotStarted($questsNotStarted);

        return $playerInfo;
    }

    /**
     * @throws GuzzleException
     */
    public function getQuests(string $player): QuestResponse
    {
        $client = new Client();

        $response = $client->request(
            'GET',
            'https://apps.runescape.com/runemetrics/quests',
            [
                RequestOptions::QUERY => [
                    'user' => $player
                ]
            ]
        );

        return $this->serializer->deserialize(
            $response->getBody()->getContents(),
            QuestResponse::class,
            'json'
        );
    }

    /**
     * @throws GuzzleException
     */
    public function getClanName(string $player): string
    {
        $client = new Client();
        $response = $client->request(
            'GET',
            'https://services.runescape.com/m=website-data/playerDetails.ws',
            [
                RequestOptions::QUERY => [
                    'membership' => true,
                    'names' => $this->serializer->serialize([$player], 'json'),
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