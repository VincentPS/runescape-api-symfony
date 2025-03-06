<?php

namespace App\Service;

use App\Trait\GuzzleCachedClientTrait;
use DateTimeImmutable;
use GuzzleHttp\Exception\GuzzleException;

class DoubleXpService
{
    use GuzzleCachedClientTrait;

    public function isDoubleXpLive(): bool
    {
        try {
            $response = $this->getClient()->get('https://rs.runescape.com/en-GB/double-xp');
            $content = $response->getBody()->getContents();

            preg_match('/<script id="__NEXT_DATA__" type="application\/json">(.*?)<\/script>/s', $content, $matches);

            if (isset($matches[1])) {
                /**
                 * @var null|array{
                 *     props: array{
                 *          pageProps: array{
                 *              doubleXpDetails: array{
                 *                  endDateTime: string
                 *              }
                 *          }
                 *     }
                 * } $jsonData
                 */
                $jsonData = json_decode($matches[1], true);

                if ($jsonData === null) {
                    return false;
                }

                if (!empty($jsonData['props']['pageProps']['doubleXpDetails']['endDateTime'])) {
                    $endDateTime = DateTimeImmutable::createFromFormat(
                        'Y-m-d\TH:i:s\Z',
                        $jsonData['props']['pageProps']['doubleXpDetails']['endDateTime']
                    );

                    if ($endDateTime instanceof DateTimeImmutable && $endDateTime > new DateTimeImmutable()) {
                        return true;
                    }
                }
            }

            return false;
        } catch (GuzzleException) {
            return false;
        }
    }
}
