<?php

namespace App\Tests\Doctrine\Builder;

use App\Dto\SkillValue;
use App\Enum\SkillEnum;

class SkillValueBuilder
{
    private SkillValue $skillValue;

    public function __construct()
    {
        $this->skillValue = new SkillValue();
    }

    public function build(): SkillValue
    {
        return $this->skillValue;
    }

    public function withId(?SkillEnum $id): self
    {
        $this->skillValue->id = $id;
        return $this;
    }

    public function withLevel(?int $level): self
    {
        $this->skillValue->level = $level;
        return $this;
    }

    public function withXp(int|float|null $xp): self
    {
        $this->skillValue->xp = $xp;
        return $this;
    }

    public function withRank(?int $rank): self
    {
        $this->skillValue->rank = $rank;
        return $this;
    }
}
