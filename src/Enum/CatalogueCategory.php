<?php

namespace App\Enum;

enum CatalogueCategory: int
{
    case All = 999;
    case Ammo = 1;
    case Archaeology_Materials = 41;
    case Arrows = 2;
    case Bolts = 3;
    case Construction_Materials = 4;
    case Construction_Products = 5;
    case Cooking_Ingredients = 6;
    case Costumes = 7;
    case Crafting_Materials = 8;
    case Familiars = 9;
    case Farming_Produce = 10;
    case Firemaking_Products = 40;
    case Fletching_Materials = 11;
    case Food_and_Drink = 12;
    case Herblore_Materials = 13;
    case Hunting_Equipment = 14;
    case Hunting_Produce = 15;
    case Jewellery = 16;
    case Mage_Armour = 17;
    case Mage_Weapons = 18;
    case Melee_Armour_High_Level = 21;
    case Melee_Armour_Low_Level = 19;
    case Melee_Armour_Mid_Level = 20;
    case Melee_Weapons_High_Level = 24;
    case Melee_Weapons_Low_Level = 22;
    case Melee_Weapons_Mid_Level = 23;
    case Mining_and_Smithing = 25;
    case Miscellaneous = 0;
    case Necromancy_Armour = 43;
    case Pocket_Items = 37;
    case Potions = 26;
    case Prayer_Armour = 27;
    case Prayer_Materials = 28;
    case Range_Armour = 29;
    case Range_Weapons = 30;
    case Runecrafting = 31;
    case Runes_Spells_Teleports = 32;
    case Salvage = 39;
    case Seeds = 33;
    case Stone_Spirits = 38;
    case Summoning_Scrolls = 34;
    case Tools_and_Containers = 35;
    case Woodcutting_Product = 36;
    case Wood_Spirits = 42;

    /**
     * @return array<string, int>
     */
    public static function getRepresentableCases(): array
    {
        $cases = self::cases();

        foreach ($cases as $case) {
            $representableCases[str_replace('_', ' ', $case->name)] = $case->value;
        }

        return $representableCases ?? [];
    }
}
