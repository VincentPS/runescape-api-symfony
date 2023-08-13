<?php

namespace App\Tests\Doctrine\Builder;

use App\Dto\Quest;
use App\Enum\QuestDifficulty;
use App\Enum\QuestStatus;

class QuestBuilder
{
    private Quest $quest;

    public function __construct()
    {
        $this->quest = new Quest();
    }

    public function build(): Quest
    {
        return $this->quest;
    }

    public function withTitle(?string $title): self
    {
        $this->quest->title = $title;
        return $this;
    }

    public function withStatus(?QuestStatus $status): self
    {
        $this->quest->status = $status;
        return $this;
    }

    public function withDifficulty(?QuestDifficulty $difficulty): self
    {
        $this->quest->difficulty = $difficulty;
        return $this;
    }

    public function withMembers(?bool $members): self
    {
        $this->quest->members = $members;
        return $this;
    }

    public function withQuestPoints(?int $questPoints): self
    {
        $this->quest->questPoints = $questPoints;
        return $this;
    }

    public function withUserEligible(?bool $userEligible): self
    {
        $this->quest->userEligible = $userEligible;
        return $this;
    }
}
