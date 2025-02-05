<?php

namespace App\Enum;

enum KnownPlayers: string
{
    case VincentS = 'VincentS'; // main
    case Dapestave = 'Dapestave'; // ironman
    case Erwtie = 'Erwtie'; // main 2

    public static function currentMain(): self
    {
        return self::Erwtie;
    }

    public static function currentMainAsString(): string
    {
        return self::currentMain()->value;
    }
}
