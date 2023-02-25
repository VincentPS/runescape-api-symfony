<?php

namespace App\Service;

use App\Dto\PlayerInfo;
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
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

class RsApi
{
    private string $clanUrl = 'https://services.runescape.com/m=clan-hiscores/members_lite.ws?clanName=%s';
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
     * @return PlayerInfo
     * @throws GuzzleException
     */
    public function getProfile(string $player): PlayerInfo
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
        $clan = $this->serializer->decode($playerDetails[1], 'json')[0]['clan'];

        $response = $client->request(
            'GET',
            'https://apps.runescape.com/runemetrics/profile/profile',
            [
                RequestOptions::QUERY => [
                    'user' => $player,
                    'activities' => 25,
                    // 't' => time() use to force refresh of data
                ]
            ]
        );

        /** @var PlayerInfo $playerInfo */
        $playerInfo = $this->serializer->deserialize(
            $response->getBody()->getContents(),
            PlayerInfo::class,
            'json'
        );

        $playerInfo->setClan($clan);

        return $playerInfo;
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ClientExceptionInterface
     */
    public function getClanList(string $clan, bool $onlyNames = false): ?array
    {
        $response = $this->client->request('GET', sprintf($this->clanUrl, $this->clean($clan)));

        if ($response->getHeaders()['content-type'][0] !== 'text/comma-separated-values') {
            return null;
        }

        $clanListCsv = $response->getContent();

        $clanList = [];
        $csvRows = explode("\n", $clanListCsv);

        foreach ($csvRows as $key => $row) {
            if ($key === 0 || $key >= count($csvRows) - 1) {
                continue;
            }

            $columns = explode(',', $row);

            if ($onlyNames === true) {
                $clanList[] = $this->clean(utf8_encode($columns[0]));
            } else {
                $clanList[] = [
                    'name' => $this->clean(utf8_encode($columns[0])),
                    'rank' => $columns[1],
                    'clan_xp' => $columns[2],
                    'clan_kills' => $columns[3],
                ];
            }
        }

        return $clanList;
    }

    private function clean(string $input): string
    {
        return \preg_replace('/[^A-Za-z0-9]++/', '_', strtolower($input));
    }
}