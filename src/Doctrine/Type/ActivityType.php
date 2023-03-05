<?php

namespace App\Doctrine\Type;

use App\Dto\Activity;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Exception;
use MartinGeorgiev\Doctrine\DBAL\Types\Jsonb;
use Symfony\Component\PropertyInfo\Extractor\ReflectionExtractor;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Component\Serializer\Normalizer\ArrayDenormalizer;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;

class ActivityType extends Jsonb
{
    public const NAME = 'activity';

    public function getName(): string
    {
        return self::NAME;
    }

    /**
     * @throws Exception
     */
    public function convertToPHPValue($value, AbstractPlatform $platform): array
    {
        return $this
            ->getSerializer()
            ->denormalize(
                parent::convertToPHPValue($value, $platform),
                Activity::class . '[]'
            );
    }

    /**
     * @throws ExceptionInterface
     */
    public function convertToDatabaseValue($value, AbstractPlatform $platform): ?string
    {
        return parent::convertToDatabaseValue(
            $this->getSerializer()->normalize($value, Activity::class . '[]'),
            $platform
        );
    }

    private function getSerializer(): Serializer
    {
        return new Serializer(
            normalizers: [
                new DateTimeNormalizer(['datetime_format' => 'Y-m-d H:i:s']),
                new ArrayDenormalizer(),
                new ObjectNormalizer(propertyTypeExtractor: new ReflectionExtractor())
            ]
        );
    }
}
