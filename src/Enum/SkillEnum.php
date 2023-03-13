<?php

namespace App\Enum;

enum SkillEnum: int
{
    case Attack = 0;
    case Defence = 1;
    case Strength = 2;
    case Constitution = 3;
    case Ranged = 4;
    case Prayer = 5;
    case Magic = 6;
    case Cooking = 7;
    case Woodcutting = 8;
    case Fletching = 9;
    case Fishing = 10;
    case Firemaking = 11;
    case Crafting = 12;
    case Smithing = 13;
    case Mining = 14;
    case Herblore = 15;
    case Agility = 16;
    case Thieving = 17;
    case Slayer = 18;
    case Farming = 19;
    case Runecrafting = 20;
    case Hunter = 21;
    case Construction = 22;
    case Summoning = 23;
    case Dungeoneering = 24;
    case Divination = 25;
    case Invention = 26;
    case Archaeology = 27;

    /**
     * @return array<string, int>
     */
    public static function toArray(): array
    {
        $skills = [];

        foreach (self::cases() as $case) {
            $skills[$case->name] = $case->value;
        }

        return $skills;
    }
}
