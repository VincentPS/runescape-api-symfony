<?php

namespace App\Dto;

use App\Enum\SkillEnum;

class SkillValue
{
    private ?SkillEnum $id = null;
    private ?int $level = null;
    private ?float $xp = null;
    private ?int $rank = null;

    /**
     * @return SkillEnum|null
     */
    public function getId(): ?SkillEnum
    {
        return $this->id;
    }

    /**
     * @param SkillEnum|null $id
     * @return SkillValue
     */
    public function setId(?SkillEnum $id): SkillValue
    {
        $this->id = $id;
        return $this;
    }

    /**
     * @return int|null
     */
    public function getLevel(): ?int
    {
        return $this->level;
    }

    /**
     * @param int|null $level
     * @return SkillValue
     */
    public function setLevel(?int $level): SkillValue
    {
        $this->level = $level;
        return $this;
    }

    /**
     * @return float|null
     */
    public function getXp(): ?float
    {
        return $this->xp;
    }

    /**
     * @param float|null $xp
     * @return SkillValue
     */
    public function setXp(?float $xp): SkillValue
    {
        $this->xp = $xp;
        return $this;
    }

    /**
     * @return int|null
     */
    public function getRank(): ?int
    {
        return $this->rank;
    }

    /**
     * @param int|null $rank
     * @return SkillValue
     */
    public function setRank(?int $rank): SkillValue
    {
        $this->rank = $rank;
        return $this;
    }
}
