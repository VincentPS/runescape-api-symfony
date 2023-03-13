<?php

namespace App\Doctrine\Type;

use App\Dto\Activity;
use App\Dto\Quest;
use App\Dto\SkillValue;
use App\Enum\QuestDifficulty;
use App\Enum\QuestStatus;
use App\Enum\SkillEnum;
use DateTime;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\ConversionException;
use Doctrine\DBAL\Types\JsonType;
use Exception;
use InvalidArgumentException;
use MartinGeorgiev\Doctrine\DBAL\Types\Jsonb;
use Symfony\Component\PropertyInfo\Extractor\ReflectionExtractor;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Component\Serializer\Normalizer\ArrayDenormalizer;
use Symfony\Component\Serializer\Normalizer\BackedEnumNormalizer;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;

class QuestType extends Jsonb
{
    public const NAME = 'quest';

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
                Quest::class . '[]'
            );
    }

    /**
     * @throws ExceptionInterface
     */
    public function convertToDatabaseValue($value, AbstractPlatform $platform): ?string
    {
        return parent::convertToDatabaseValue(
            $this->getSerializer()->normalize($value, Quest::class . '[]'),
            $platform
        );
    }

    private function getSerializer(): Serializer
    {
        return new Serializer(
            normalizers: [
                new DateTimeNormalizer(['datetime_format' => 'Y-m-d H:i:s']),
                new ArrayDenormalizer(),
                new BackedEnumNormalizer(),
                new ObjectNormalizer(propertyTypeExtractor: new ReflectionExtractor())
            ]
        );
    }
}
