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

    public function graphColor(): string
    {
        return match ($this) {
            self::Agility => 'rgb(40, 74, 149)',
            self::Archaeology => 'rgb(185, 87, 30)',
            self::Attack => 'rgb(152, 20, 20)',
            self::Constitution => 'rgb(170, 206, 218)',
            self::Construction => 'rgb(168, 186, 188)',
            self::Cooking => 'rgb(85, 50, 133)',
            self::Crafting => 'rgb(182, 149, 44)',
            self::Defence => 'rgb(20, 126, 152)',
            self::Divination => 'rgb(148, 63, 186)',
            self::Dungeoneering => 'rgb(114, 57, 32)',
            self::Farming => 'rgb(31, 125, 84)',
            self::Firemaking => 'rgb(247, 95, 40)',
            self::Fishing => 'rgb(62, 112, 185)',
            self::Fletching => 'rgb(20, 152, 147)',
            self::Herblore => 'rgb(18, 69, 58)',
            self::Hunter => 'rgb(195, 139, 78)',
            self::Invention => 'rgb(247, 181, 40)',
            self::Magic => 'rgb(195, 227, 220)',
            self::Mining => 'rgb(86, 73, 94)',
            self::Necromancy => 'rgb(156, 142, 255)',
            self::Prayer => 'rgb(109, 191, 242)',
            self::Ranged => 'rgb(19, 183, 81)',
            self::Runecrafting => 'rgb(215, 235, 163)',
            self::Slayer => 'rgb(72, 65, 47)',
            self::Smithing => 'rgb(101, 136, 126)',
            self::Strength => 'rgb(19, 183, 135)',
            self::Summoning => 'rgb(222, 161, 176)',
            self::Thieving => 'rgb(54, 23, 94)',
            self::Woodcutting => 'rgb(126, 79, 53)',
        };
    }

    public function isElite(): bool
    {
        return match ($this) {
            self::Invention => true,
            default => false,
        };
    }
}
