<?php

namespace App\Enum;

enum ActivityFilter: string
{
    case All = 'All';
    case Bosses = 'Bosses';
    case Loot = 'Loot';
    case Pets = 'Pets';
    case Quests = 'Quests';
    case Skills = 'Skills';

    /**
     * @return string[]
     */
    public static function getValues(): array
    {
        $values = [];

        foreach (self::cases() as $enumValue) {
            $values[$enumValue->value] = strtolower($enumValue->name);
        }

        return $values;
    }
}
