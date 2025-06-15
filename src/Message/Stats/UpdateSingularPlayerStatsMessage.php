<?php

namespace App\Message\Stats;

use Symfony\Component\Messenger\Attribute\AsMessage;

#[AsMessage('async')]
final class UpdateSingularPlayerStatsMessage
{
    public function __construct(
        public string $player
    ) {
    }
}
