<?php

namespace App\Enum;

enum KnownPlayers: string
{
    case VincentS = 'VincentS'; // main
    case Dapestave = 'Dapestave'; // ironman
    case Play_Caky = 'Play Caky'; // dennis
    case Erwin = 'Erwin'; // erwin
    case CollectOres = 'CollectOres'; // no idea

    public static function currentMain(): self
    {
        return self::VincentS;
    }

    public static function currentMainAsString(): string
    {
        return self::currentMain()->value;
    }
}
