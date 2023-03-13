<?php

namespace App\Service;

class ArrayHelper
{
    public function makeSkillValuesArray(array $array): array
    {
        return array_map(fn($skill): array => [
            'level' => $skill['level'],
            'xp' => $skill['xp'],
            'rank' => $skill['rank'],
            'skillId' => $skill['id'],
        ], $array);
    }
}
