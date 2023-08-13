<?php

namespace App\Tests\Doctrine\Type;

use App\Doctrine\Type\ActivityType;
use App\Doctrine\Type\CustomJsonbType;
use App\Doctrine\Type\QuestType;
use App\Doctrine\Type\SkillValueType;
use App\Dto\Activity;
use App\Dto\Quest;
use App\Dto\SkillValue;
use App\Enum\QuestDifficulty;
use App\Enum\QuestStatus;
use App\Enum\SkillEnum;
use App\Exception\Jsonb\JsonbConvertToDatabaseValueException;
use App\Exception\Jsonb\JsonbConvertToPHPValueException;
use App\Tests\Doctrine\Builder\ActivityBuilder;
use App\Tests\Doctrine\Builder\QuestBuilder;
use App\Tests\Doctrine\Builder\SkillValueBuilder;
use DateTimeImmutable;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use PHPUnit\Framework\TestCase;
use Symfony\Component\PropertyInfo\Extractor\ReflectionExtractor;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Exception\NotEncodableValueException;
use Symfony\Component\Serializer\Normalizer\ArrayDenormalizer;
use Symfony\Component\Serializer\Normalizer\BackedEnumNormalizer;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;

class CustomJsonbTypeTest extends TestCase
{
    /**
     * @param SkillValue[]|Activity[]|Quest[] $dtoObjects
     * @dataProvider classesDataProvider
     * @throws JsonbConvertToDatabaseValueException
     */
    public function testConvertToDatabaseValue(
        string $classFQCN,
        string $dtoFQCN,
        array $dtoObjects,
        string $databaseValue
    ): void {
        $abstractPlatform = $this->createMock(AbstractPlatform::class);

        /** @var CustomJsonbType $fieldType */
        $fieldType = new $classFQCN();

        $result = $fieldType->convertToDatabaseValue($dtoObjects, $abstractPlatform);

        $this->assertIsString($result);
        $this->assertEquals(
            $databaseValue,
            $result,
            'Make sure a json string is returned containing the ' . $dtoFQCN . '(s)'
        );
    }

    /**
     * @param SkillValue[]|Activity[]|Quest[] $dtoObjects
     * @dataProvider classesDataProvider
     * @throws JsonbConvertToPHPValueException
     */
    public function testConvertToPHPValue(
        string $classFQCN,
        string $dtoFQCN,
        array $dtoObjects,
        string $databaseValue
    ): void {
        $abstractPlatform = $this->createMock(AbstractPlatform::class);
        $serializer = new Serializer(
            normalizers: [
                new DateTimeNormalizer(['datetime_format' => 'Y-m-d H:i:s']),
                new BackedEnumNormalizer(),
                new ArrayDenormalizer(),
                new ObjectNormalizer(propertyTypeExtractor: new ReflectionExtractor())
            ],
            encoders: [
                new JsonEncoder()
            ]
        );

        /** @var CustomJsonbType $fieldType */
        $fieldType = new $classFQCN();

        $result = $fieldType->convertToPHPValue($databaseValue, $abstractPlatform);
        $dtos = $serializer->deserialize($databaseValue, $dtoFQCN . '[]', 'json');

        $this->assertEquals(
            $dtos,
            $result,
            'Makes sure that the database value is converted to the correct ' . $dtoFQCN . ' object(s)'
        );
    }

    /**
     * @dataProvider classesDataProvider
     * @throws JsonbConvertToPHPValueException
     */
    public function testThatAnInvalidJsonStringThrowsAnException(string $classFQCN): void
    {
        $abstractPlatform = $this->createMock(AbstractPlatform::class);
        $databaseValue = 'invalid json string';

        /** @var CustomJsonbType $fieldType */
        $fieldType = new $classFQCN();

        $this->expectException(NotEncodableValueException::class);
        $this->expectExceptionMessage('Syntax error');
        $fieldType->convertToPHPValue($databaseValue, $abstractPlatform);
    }

    /**
     * @return iterable<string, array{
     *     classFQCN: string,
     *     dtoFQCN: string,
     *     dtoObjects: SkillValue[]|Activity[]|Quest[],
     *     databaseValue: string
     * }>
     */
    public function classesDataProvider(): iterable
    {
        yield SkillValueType::class => [
            'classFQCN' => SkillValueType::class,
            'dtoFQCN' => SkillValue::class,
            'dtoObjects' => [
                (new SkillValueBuilder())
                    ->withId(SkillEnum::Necromancy)
                    ->withLevel(120)
                    ->withXp(200000000)
                    ->withRank(1)
                    ->build(),
                (new SkillValueBuilder())
                    ->withId(SkillEnum::Archaeology)
                    ->withLevel(120)
                    ->withXp(123456789)
                    ->withRank(2)
                    ->build()
            ],
            'databaseValue' => '[{"id":28,"level":120,"xp":200000000,"rank":1},' .
                '{"id":27,"level":120,"xp":123456789,"rank":2}]',
        ];

        $activityDate = new DateTimeImmutable();
        $activityDateMinusOneDay = new DateTimeImmutable('-1 day');

        yield ActivityType::class => [
            'classFQCN' => ActivityType::class,
            'dtoFQCN' => Activity::class,
            'dtoObjects' => [
                (new ActivityBuilder())
                    ->withDate($activityDate)
                    ->withDetails('activity 1 - details')
                    ->withText('activity 1 - text')
                    ->build(),
                (new ActivityBuilder())
                    ->withDate($activityDateMinusOneDay)
                    ->withDetails('activity 2 - details')
                    ->withText('activity 2 - text')
                    ->build()
            ],
            'databaseValue' => '[{"date":"' . $activityDate->format('Y-m-d H:i:s') . '","details":"act' .
                'ivity 1 - details","text":"activity 1 - text"},{"date":"' .
                $activityDateMinusOneDay->format('Y-m-d H:i:s') . '","details":"activity 2 - details",' .
                '"text":"activity 2 - text"}]'
        ];

        yield QuestType::class => [
            'classFQCN' => QuestType::class,
            'dtoFQCN' => Quest::class,
            'dtoObjects' => [
                (new QuestBuilder())
                    ->withTitle('Test title')
                    ->withStatus(QuestStatus::Completed)
                    ->withDifficulty(QuestDifficulty::Grandmaster)
                    ->withMembers(true)
                    ->withUserEligible(true)
                    ->withQuestPoints(4)
                    ->build(),
                (new QuestBuilder())
                    ->withTitle('Test title 2')
                    ->withStatus(QuestStatus::NotStarted)
                    ->withDifficulty(QuestDifficulty::Master)
                    ->withMembers(true)
                    ->withUserEligible(true)
                    ->withQuestPoints(2)
                    ->build()
            ],
            'databaseValue' => '[{"title":"Test title","status":"COMPLETED","difficulty":4,"members":true,' .
                '"questPoints":4,"userEligible":true},{"title":"Test title 2","status":"NOT_STARTED","difficulty":' .
                '3,"members":true,"questPoints":2,"userEligible":true}]'
        ];
    }
}
