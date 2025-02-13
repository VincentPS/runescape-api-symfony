<?php

namespace App\Scheduler;

use App\Enum\UpdateAllPlayersType;
use App\Message\Stats\UpdateAllPlayersMessage;
use Symfony\Component\Scheduler\Attribute\AsSchedule;
use Symfony\Component\Scheduler\RecurringMessage;
use Symfony\Component\Scheduler\Schedule;
use Symfony\Component\Scheduler\ScheduleProviderInterface;

#[AsSchedule('update_all_inactive_players')]
class UpdateAllInactivePlayers implements ScheduleProviderInterface
{
    public function getSchedule(): Schedule
    {
        return (new Schedule())
            ->add(
                RecurringMessage::cron(
                    '0 * * * *', // Every hour
                    new UpdateAllPlayersMessage(UpdateAllPlayersType::INACTIVE)
                )
            );
    }
}
