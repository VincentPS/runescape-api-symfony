<?php

namespace App\Enum;

enum QuestStatus: string
{
    case Completed = 'COMPLETED';
    case Started = 'STARTED';
    case NotStarted = 'NOT_STARTED';
}
