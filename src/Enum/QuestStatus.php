<?php

namespace App\Enum;

Enum QuestStatus: string
{
    case Completed = 'COMPLETED';
    case Started = 'STARTED';
    case NotStarted = 'NOT_STARTED';
}