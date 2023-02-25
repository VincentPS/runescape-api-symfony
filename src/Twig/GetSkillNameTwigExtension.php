<?php

namespace App\Twig;

use App\Enum\SkillEnum;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class GetSkillNameTwigExtension extends AbstractExtension
{
    public function getFunctions(): array
    {
        return [
            new TwigFunction('get_skill_name', [$this, 'getSkillName']),
        ];
    }

    public function getSkillName(string $input): ?string
    {
        foreach (SkillEnum::toArray() as $skillName => $skill) {
            if (stripos($input, $skillName) !== false) {
                return $skillName;
            }
        }

        return null;
    }

}