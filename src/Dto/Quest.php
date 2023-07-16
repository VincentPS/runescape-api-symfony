<?php

namespace App\Dto;

use App\Enum\QuestDifficulty;
use App\Enum\QuestStatus;

class Quest implements JsonbDTOInterface
{
    public ?string $title = null;
    public ?QuestStatus $status = null;
    public ?QuestDifficulty $difficulty = null;
    public ?bool $members = null;
    public ?int $questPoints = null;
    public ?bool $userEligible = null;
}
