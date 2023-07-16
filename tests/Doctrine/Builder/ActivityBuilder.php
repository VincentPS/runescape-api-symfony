<?php

namespace App\Tests\Doctrine\Builder;

use App\Dto\Activity;
use DateTimeImmutable;

class ActivityBuilder
{
    private Activity $activity;

    public function __construct()
    {
        $this->activity = new Activity();
    }

    public function build(): Activity
    {
        return $this->activity;
    }

    public function withDate(?DateTimeImmutable $dateTimeImmutable = null): self
    {
        if ($dateTimeImmutable === null) {
            $dateTimeImmutable = new DateTimeImmutable();
        }

        $this->activity->date = $dateTimeImmutable;
        return $this;
    }

    public function withDetails(?string $details = 'details'): self
    {
        $this->activity->details = $details;
        return $this;
    }

    public function withText(?string $text = 'text'): self
    {
        $this->activity->text = $text;
        return $this;
    }
}
