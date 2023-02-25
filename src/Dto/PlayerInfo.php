<?php

namespace App\Dto;

class PlayerInfo
{
    private ?int $totalSkill = null;
    private ?int $totalXp = null;
    private ?string $rank = null;
    private ?int $combatLevel = null;
    private ?string $name = null;
    private ?string $clan = null;

    /** @var Activity[]|null */
    private ?array $activities = [];

    /** @var SkillValue[]|null */
    private ?array $skillValues = [];

    /**
     * @return int|null
     */
    public function getTotalSkill(): ?int
    {
        return $this->totalSkill;
    }

    /**
     * @param int|null $totalSkill
     * @return PlayerInfo
     */
    public function setTotalSkill(?int $totalSkill): PlayerInfo
    {
        $this->totalSkill = $totalSkill;
        return $this;
    }

    /**
     * @return int|null
     */
    public function getTotalXp(): ?int
    {
        return $this->totalXp;
    }

    /**
     * @param int|null $totalXp
     * @return PlayerInfo
     */
    public function setTotalXp(?int $totalXp): PlayerInfo
    {
        $this->totalXp = $totalXp;
        return $this;
    }

    /**
     * @return int|null
     */
    public function getRank(): ?int
    {
        return intval(str_replace(',', '', strval($this->rank)));
    }

    /**
     * @param string|null $rank
     * @return PlayerInfo
     */
    public function setRank(?string $rank): PlayerInfo
    {
        $this->rank = $rank;
        return $this;
    }

    /**
     * @return int|null
     */
    public function getCombatLevel(): ?int
    {
        return $this->combatLevel;
    }

    /**
     * @param int|null $combatLevel
     * @return PlayerInfo
     */
    public function setCombatLevel(?int $combatLevel): PlayerInfo
    {
        $this->combatLevel = $combatLevel;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * @param string|null $name
     * @return PlayerInfo
     */
    public function setName(?string $name): PlayerInfo
    {
        $this->name = $name;
        return $this;
    }

    /**
     * @return Activity[]|null
     */
    public function getActivities(): ?array
    {
        return $this->activities;
    }

    /**
     * @param Activity[]|null $activities
     * @return PlayerInfo
     */
    public function setActivities(?array $activities): PlayerInfo
    {
        $this->activities = $activities;
        return $this;
    }

    /**
     * @param Activity $adventureLogItem
     * @return $this
     */
    public function addActivity(Activity $adventureLogItem): PlayerInfo
    {
        $this->activities[] = $adventureLogItem;
        return $this;
    }

    /**
     * @return SkillValue[]|null
     */
    public function getSkillValues(): ?array
    {
        return $this->skillValues;
    }

    /**
     * @param SkillValue[]|null $skillValues
     * @return PlayerInfo
     */
    public function setSkillValues(?array $skillValues): PlayerInfo
    {
        $this->skillValues = $skillValues;
        return $this;
    }

    /**
     * @param SkillValue $skillValue
     * @return $this
     */
    public function addSkillValue(SkillValue $skillValue): PlayerInfo
    {
        $this->skillValues[] = $skillValue;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getClan(): ?string
    {
        return $this->clan;
    }

    /**
     * @param string|null $clan
     * @return PlayerInfo
     */
    public function setClan(?string $clan): PlayerInfo
    {
        $this->clan = $clan;
        return $this;
    }
}