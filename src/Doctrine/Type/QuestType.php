<?php

namespace App\Doctrine\Type;

use App\Dto\Quest;

class QuestType extends CustomJsonbType
{
    public const NAME = 'quest';

    public function getName(): string
    {
        return self::NAME;
    }

    protected function getDtoFqcn(): string
    {
        return Quest::class;
    }
}
