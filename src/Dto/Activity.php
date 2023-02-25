<?php

namespace App\Dto;

use DateTime;

class Activity
{
    private ?DateTime $date = null;
    private ?string $details = null;
    private ?string $text = null;

    /**
     * @return DateTime|null
     */
    public function getDate(): ?DateTime
    {
        return $this->date;
    }

    /**
     * @param DateTime|null $date
     * @return Activity
     */
    public function setDate(?DateTime $date): Activity
    {
        $this->date = $date;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getDetails(): ?string
    {
        return $this->details;
    }

    /**
     * @param string|null $details
     * @return Activity
     */
    public function setDetails(?string $details): Activity
    {
        $this->details = $details;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getText(): ?string
    {
        return $this->text;
    }

    /**
     * @param string|null $text
     * @return Activity
     */
    public function setText(?string $text): Activity
    {
        $this->text = $text;
        return $this;
    }
}