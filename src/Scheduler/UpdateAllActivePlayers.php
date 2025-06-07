<?php

namespace App\Scheduler;

use App\Enum\UpdateAllPlayersType;
use App\Message\Stats\UpdateAllPlayersMessage;
use Symfony\Component\Scheduler\Attribute\AsSchedule;
use Symfony\Component\Scheduler\RecurringMessage;
use Symfony\Component\Scheduler\Schedule;
use Symfony\Component\Scheduler\ScheduleProviderInterface;

#[AsSchedule('update_all_active_players')]
class UpdateAllActivePlayers implements ScheduleProviderInterface
{
    public function getSchedule(): Schedule
    {
        return (new Schedule())
            ->add(
                RecurringMessage::cron(
                    '*/3 * * * *', // Every 3 minutes because we also batch the updates
                    new UpdateAllPlayersMessage(UpdateAllPlayersType::ACTIVE)
                )
            );
    }
}
