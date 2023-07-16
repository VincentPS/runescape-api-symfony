<?php

namespace App\Doctrine\Type;

use App\Dto\SkillValue;

class SkillValueType extends CustomJsonbType
{
    public const NAME = 'skillValue';

    public function getName(): string
    {
        return self::NAME;
    }

    protected function getDtoFqcn(): string
    {
        return SkillValue::class;
    }
}
