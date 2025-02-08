<?php

namespace App\Scheduler;

use App\Message\UpdateAllUsers;
use Symfony\Component\Scheduler\Attribute\AsSchedule;
use Symfony\Component\Scheduler\RecurringMessage;
use Symfony\Component\Scheduler\Schedule;
use Symfony\Component\Scheduler\ScheduleProviderInterface;

#[AsSchedule('update_player_data')]
class UpdatePlayerDataProvider implements ScheduleProviderInterface
{
    public function getSchedule(): Schedule
    {
        return (new Schedule())->add(RecurringMessage::cron('*/10 * * * *', new UpdateAllUsers()));
    }
}
