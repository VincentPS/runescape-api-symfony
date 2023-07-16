<?php

namespace App\Dto;

class QuestResponse
{
    private ?string $loggedIn = null;

    /** @var Quest[]|null */
    private ?array $quests = null;

    public function getLoggedIn(): ?string
    {
        return $this->loggedIn;
    }

    public function setLoggedIn(?string $loggedIn): QuestResponse
    {
        $this->loggedIn = $loggedIn;
        return $this;
    }

    /**
     * @return Quest[]|null
     */
    public function getQuests(): ?array
    {
        return $this->quests;
    }

    /**
     * @param Quest[]|null $quests
     * @return QuestResponse
     */
    public function setQuests(?array $quests): QuestResponse
    {
        $this->quests = $quests;
        return $this;
    }

    public function addQuest(Quest $quest): QuestResponse
    {
        $this->quests[] = $quest;
        return $this;
    }
}
