<?php

namespace App\Dto;

use App\Enum\QuestDifficulty;
use App\Enum\QuestStatus;

class Quest
{
    private ?string $title = null;
    private ?QuestStatus $status = null;
    private ?QuestDifficulty $difficulty = null;
    private ?bool $members = null;
    private ?int $questPoints = null;
    private ?bool $userEligible = null;

    /**
     * @return string|null
     */
    public function getTitle(): ?string
    {
        return $this->title;
    }

    /**
     * @param string|null $title
     * @return Quest
     */
    public function setTitle(?string $title): Quest
    {
        $this->title = $title;
        return $this;
    }

    /**
     * @return QuestStatus|null
     */
    public function getStatus(): ?QuestStatus
    {
        return $this->status;
    }

    /**
     * @param QuestStatus|null $status
     * @return Quest
     */
    public function setStatus(?QuestStatus $status): Quest
    {
        $this->status = $status;
        return $this;
    }

    /**
     * @return QuestDifficulty|null
     */
    public function getDifficulty(): ?QuestDifficulty
    {
        return $this->difficulty;
    }

    /**
     * @param QuestDifficulty|null $difficulty
     * @return Quest
     */
    public function setDifficulty(?QuestDifficulty $difficulty): Quest
    {
        $this->difficulty = $difficulty;
        return $this;
    }

    /**
     * @return bool|null
     */
    public function getMembers(): ?bool
    {
        return $this->members;
    }

    /**
     * @param bool|null $members
     * @return Quest
     */
    public function setMembers(?bool $members): Quest
    {
        $this->members = $members;
        return $this;
    }

    /**
     * @return int|null
     */
    public function getQuestPoints(): ?int
    {
        return $this->questPoints;
    }

    /**
     * @param int|null $questPoints
     * @return Quest
     */
    public function setQuestPoints(?int $questPoints): Quest
    {
        $this->questPoints = $questPoints;
        return $this;
    }

    /**
     * @return bool|null
     */
    public function getUserEligible(): ?bool
    {
        return $this->userEligible;
    }

    /**
     * @param bool|null $userEligible
     * @return Quest
     */
    public function setUserEligible(?bool $userEligible): Quest
    {
        $this->userEligible = $userEligible;
        return $this;
    }
}
