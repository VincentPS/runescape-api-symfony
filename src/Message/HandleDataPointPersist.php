<?php

namespace App\Message;

use App\Entity\Player;

final class HandleDataPointPersist implements AsyncEventInterface
{
    public function __construct(public Player $dataPoint)
    {
    }
}
