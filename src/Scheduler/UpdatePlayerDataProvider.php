<?php

namespace App\Scheduler;

use App\Enum\KnownPlayers;
use App\Message\FetchLatestApiData;
use Symfony\Component\Scheduler\Attribute\AsSchedule;
use Symfony\Component\Scheduler\RecurringMessage;
use Symfony\Component\Scheduler\Schedule;
use Symfony\Component\Scheduler\ScheduleProviderInterface;

#[AsSchedule('update_player_data')]
class UpdatePlayerDataProvider implements ScheduleProviderInterface
{
    public function getSchedule(): Schedule
    {
        return (new Schedule())->add(
            RecurringMessage::cron(
                '*/1 * * * *',
                new FetchLatestApiData(KnownPlayers::VincentS->value)
            )
        );
    }
}
