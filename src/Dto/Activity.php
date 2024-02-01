<?php

namespace App\Dto;

use DateTimeImmutable;
use Symfony\Component\PropertyInfo\Extractor\ReflectionExtractor;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\ArrayDenormalizer;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;

class Activity implements JsonbDTOInterface
{
    public ?DateTimeImmutable $date = null;
    public ?string $details = null;
    public ?string $text = null;

    public static function getSerializer(): Serializer
    {
        return new Serializer(
            normalizers: [
                new DateTimeNormalizer(['datetime_format' => 'Y-m-d H:i:s']),
                new ArrayDenormalizer(),
                new ObjectNormalizer(propertyTypeExtractor: new ReflectionExtractor())
            ],
            encoders: [
                new JsonEncoder()
            ]
        );
    }
}
