<?php

namespace App\Doctrine\Type;

use App\Dto\Activity;
use App\Dto\Quest;
use App\Dto\SkillValue;
use App\Enum\SkillEnum;
use DateTime;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\ConversionException;
use Doctrine\DBAL\Types\JsonType;
use Exception;
use InvalidArgumentException;
use MartinGeorgiev\Doctrine\DBAL\Types\Jsonb;

class SkillValueType extends Jsonb
{
    public const NAME = 'skillValue';

    public function getName(): string
    {
        return self::NAME;
    }

    /**
     * @throws Exception
     */
    public function convertToPHPValue($value, AbstractPlatform $platform): array
    {
        $data = parent::convertToPHPValue($value, $platform);

        $skillValues = [];

        foreach ($data as $skillValueData) {
            $skillValue = new SkillValue();
            $skillValue->setRank($skillValueData['rank']);
            $skillValue->setXp($skillValueData['xp']);
            $skillValue->setId(SkillEnum::from($skillValueData['id']));
            $skillValue->setLevel($skillValueData['level']);
            $skillValues[] = $skillValue;
        }

        return $skillValues;
    }

    public function convertToDatabaseValue($value, AbstractPlatform $platform): ?string
    {
        if (!is_array($value)) {
            throw new InvalidArgumentException('SkillValueType only accepts arrays.');
        }

        $skillValuesData = [];

        foreach ($value as $skillValue) {
            if (!$skillValue instanceof SkillValue) {
                throw new InvalidArgumentException('SkillValueType only accepts arrays of SkillValue objects.');
            }

            $skillValuesData[] = [
                'rank' => $skillValue->getRank(),
                'xp' => $skillValue->getXp(),
                'id' => $skillValue->getId(),
                'level' => $skillValue->getLevel(),
            ];
        }

        return parent::convertToDatabaseValue($skillValuesData, $platform);
    }
}
