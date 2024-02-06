<?php

namespace App\Enum;

enum SkillEnum: int
{
    case Agility = 16;
    case Archaeology = 27;
    case Attack = 0;
    case Constitution = 3;
    case Construction = 22;
    case Cooking = 7;
    case Crafting = 12;
    case Defence = 1;
    case Divination = 25;
    case Dungeoneering = 24;
    case Farming = 19;
    case Firemaking = 11;
    case Fishing = 10;
    case Fletching = 9;
    case Herblore = 15;
    case Hunter = 21;
    case Invention = 26;
    case Magic = 6;
    case Mining = 14;
    case Necromancy = 28;
    case Prayer = 5;
    case Ranged = 4;
    case Runecrafting = 20;
    case Slayer = 18;
    case Smithing = 13;
    case Strength = 2;
    case Summoning = 23;
    case Thieving = 17;
    case Woodcutting = 8;

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
