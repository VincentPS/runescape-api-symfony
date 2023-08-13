<?php

namespace App\Tests\Doctrine\Type;

use App\Doctrine\Type\ActivityType;
use App\Dto\Activity;
use App\Exception\Jsonb\JsonbConvertToDatabaseValueException;
use App\Exception\Jsonb\JsonbConvertToPHPValueException;
use App\Tests\Doctrine\Builder\ActivityBuilder;
use DateTimeImmutable;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use PHPUnit\Framework\TestCase;
use Symfony\Component\PropertyInfo\Extractor\ReflectionExtractor;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Exception\NotEncodableValueException;
use Symfony\Component\Serializer\Normalizer\ArrayDenormalizer;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;

class ActivityTypeTest extends TestCase
{
    /**
     * @throws JsonbConvertToDatabaseValueException
     */
    public function testConvertToDatabaseValue(): void
    {
        $abstractPlatform = $this->createMock(AbstractPlatform::class);

        $dateTimeUsedForBuilder = new DateTimeImmutable();
        $dateTimeUsedForBuilderMinusOneDay = $dateTimeUsedForBuilder->modify('-1 day');

        /** @var Activity[] $activities */
        $activities = [];

        $activities[] = (new ActivityBuilder())
            ->withDate($dateTimeUsedForBuilder)
            ->withDetails('activity 1 - details')
            ->withText('activity 1 - text')
            ->build();

        $activities[] = (new ActivityBuilder())
            ->withDate($dateTimeUsedForBuilderMinusOneDay)
            ->withDetails('activity 2 - details')
            ->withText('activity 2 - text')
            ->build();

        $activityType = new ActivityType();
        $result = $activityType->convertToDatabaseValue($activities, $abstractPlatform);

        $this->assertIsString($result);
        $this->assertEquals(
            '[{"date":"' . $dateTimeUsedForBuilder->format('Y-m-d H:i:s') . '","details":"act' .
            'ivity 1 - details","text":"activity 1 - text"},{"date":"' .
            $dateTimeUsedForBuilderMinusOneDay->format('Y-m-d H:i:s') . '","details":"activity 2 - details",' .
            '"text":"activity 2 - text"}]',
            $result,
            'Make sure a json string is returned containing the activities'
        );
    }

    /**
     * @throws JsonbConvertToPHPValueException
     */
    public function testConvertToPHPValue(): void
    {
        $abstractPlatform = $this->createMock(AbstractPlatform::class);
        $serializer = new Serializer(
            normalizers: [
                new DateTimeNormalizer(['datetime_format' => 'Y-m-d H:i:s']),
                new ArrayDenormalizer(),
                new ObjectNormalizer(propertyTypeExtractor: new ReflectionExtractor())
            ],
            encoders: [
                new JsonEncoder()
            ]
        );

        $dateUsedInJson = new DateTimeImmutable();
        $dateUsedInJsonMinusOneDay = $dateUsedInJson->modify('-1 day');

        $databaseValue = '[{"date":"' . $dateUsedInJson->format('Y-m-d H:i:s') . '","details":"act' .
            'ivity 1 - details","text":"activity 1 - text"},{"date":"' .
            $dateUsedInJsonMinusOneDay->format('Y-m-d H:i:s') . '","details":"activity 2 - details",' .
            '"text":"activity 2 - text"}]';

        $activityType = new ActivityType();
        $result = $activityType->convertToPHPValue($databaseValue, $abstractPlatform);
        $activityDtos = $serializer->deserialize($databaseValue, Activity::class . '[]', 'json');

        $this->assertEquals(
            $activityDtos,
            $result,
            'Makes sure that the database value is converted to the correct Activity[]'
        );
    }

    public function testThatAnInvalidJsonStringThrowsAnException(): void
    {
        $abstractPlatform = $this->createMock(AbstractPlatform::class);
        $databaseValue = 'invalid json string';

        $activityType = new ActivityType();

        $this->expectException(NotEncodableValueException::class);
        $this->expectExceptionMessage('Syntax error');
        $activityType->convertToPHPValue($databaseValue, $abstractPlatform);
    }
}
