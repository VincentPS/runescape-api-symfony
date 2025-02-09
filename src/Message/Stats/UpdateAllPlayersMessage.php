<?php

namespace App\Message\Stats;

use Symfony\Component\Messenger\Attribute\AsMessage;

#[AsMessage('async')]
final readonly class UpdateAllPlayersMessage
{
}
