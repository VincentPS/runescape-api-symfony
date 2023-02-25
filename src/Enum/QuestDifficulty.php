<?php

namespace App\Enum;

Enum QuestDifficulty: int
{
    case Novice = 0;
    case Intermediate = 1;
    case Experienced = 2;
    case Master = 3;
    case Grandmaster = 4;
    case Special = 250;
}