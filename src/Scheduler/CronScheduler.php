<?php

namespace App\Scheduler;

use App\Enum\UpdateAllPlayersType;
use App\Message\Clan\UpdateAllPlayerClanNamesMessage;
use App\Message\Stats\UpdateAllPlayerStatsMessage;
use Symfony\Component\Scheduler\Attribute\AsSchedule;
use Symfony\Component\Scheduler\RecurringMessage;
use Symfony\Component\Scheduler\Schedule;
use Symfony\Component\Scheduler\ScheduleProviderInterface;

#[AsSchedule]
class CronScheduler implements ScheduleProviderInterface
{
    public function getSchedule(): Schedule
    {
        return (new Schedule())
            ->add(RecurringMessage::cron('*/2 * * * *', new UpdateAllPlayerStatsMessage(UpdateAllPlayersType::ACTIVE)))
            ->add(RecurringMessage::cron('0 * * * *', new UpdateAllPlayerClanNamesMessage()));
    }
}
