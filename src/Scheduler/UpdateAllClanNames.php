<?php

namespace App\Scheduler;

use App\Message\Clan\UpdateAllPlayersMessage;
use Symfony\Component\Scheduler\Attribute\AsSchedule;
use Symfony\Component\Scheduler\RecurringMessage;
use Symfony\Component\Scheduler\Schedule;
use Symfony\Component\Scheduler\ScheduleProviderInterface;

#[AsSchedule('update_clan_names')]
class UpdateAllClanNames implements ScheduleProviderInterface
{
    public function getSchedule(): Schedule
    {
        return (new Schedule())->add(RecurringMessage::cron('*/10 * * * *', new UpdateAllPlayersMessage()));
    }
}
