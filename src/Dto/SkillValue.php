<?php

namespace App\Dto;

use App\Enum\SkillEnum;

class SkillValue implements JsonbDTOInterface
{
    public ?SkillEnum $id = null;
    public ?int $level = null;
    public int|float|null $xp = null;
    public ?int $rank = null;
}
