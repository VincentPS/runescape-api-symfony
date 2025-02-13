<?php

namespace App\Message\Stats;

use App\Enum\UpdateAllPlayersType;
use Symfony\Component\Messenger\Attribute\AsMessage;

#[AsMessage('async')]
final readonly class UpdateAllPlayersMessage
{
    public function __construct(
        public UpdateAllPlayersType $type
    ) {
    }
}
