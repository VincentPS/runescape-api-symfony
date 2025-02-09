<?php

namespace App\Message\Clan;

use Symfony\Component\Messenger\Attribute\AsMessage;

#[AsMessage('async')]
final readonly class UpdateOnePlayerMessage
{
    public function __construct(
        public string $player
    ) {
    }
}
