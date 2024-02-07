<?php

namespace App\Service;

use App\Enum\CatalogueCategory;
use App\Exception\RateLimitedException;
use App\Trait\GuzzleCachedClientTrait;
use App\Trait\SerializerAwareTrait;
use App\ValueObject\GrandExchange\CatalogueResponse;
use App\ValueObject\GrandExchange\CatalogueResponseCategory;
use App\ValueObject\GrandExchange\CatalogueResponseCollection;
use Exception;
use GuzzleHttp\Exception\GuzzleException;

class GrandExchangeApiService
{
    use SerializerAwareTrait;
    use GuzzleCachedClientTrait;

    /**
     * @throws RateLimitedException
     * @throws GuzzleException
     */
    public function getItemInformationFromApi(string $itemName): CatalogueResponseCollection
    {
        $catalogueResponseCollection = new CatalogueResponseCollection();

        $categories = CatalogueCategory::cases();
        foreach ($categories as $category) {
            if ($category === CatalogueCategory::All) {
                continue;
            }

            $catalogueCategory = $this->createCatalogueReponseCategory($itemName, $category);
            $catalogueResponseCollection->addCategory($catalogueCategory);
        }

        return $catalogueResponseCollection;
    }

    /**
     * @throws RateLimitedException
     * @throws GuzzleException
     */
    public function getItemInformationFromApiByCategory(
        string $itemName,
        CatalogueCategory $category
    ): CatalogueResponseCollection {
        $catalogueCategory = $this->createCatalogueReponseCategory($itemName, $category);

        $catalogueResponseCollection = new CatalogueResponseCollection();
        $catalogueResponseCollection->addCategory($catalogueCategory);

        return $catalogueResponseCollection;
    }

    /**
     * @throws RateLimitedException
     * @throws GuzzleException
     */
    private function createCatalogueReponseCategory(
        string $itemName,
        CatalogueCategory $category
    ): CatalogueResponseCategory {
        $response = $this->getClient()->request(
            'GET',
            'https://secure.runescape.com/m=itemdb_rs/api/catalogue/items.json',
            [
                'query' => [
                    'category' => $category->value,
                    'alpha' => strtolower($itemName),
                    'page' => '1'
                ]
            ]
        );

        try {
            return new CatalogueResponseCategory(
                $category,
                $this->getSerializer()->deserialize(
                    $response->getBody()->getContents(),
                    CatalogueResponse::class,
                    'json'
                )
            );
        } catch (Exception) {
            throw new RateLimitedException('The Runescape Grand Exchange API is rate limited. Try again later.');
        }
    }
}
