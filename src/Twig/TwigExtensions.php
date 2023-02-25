<?php

namespace App\Twig;

use App\Enum\SkillEnum;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

class TwigExtensions extends AbstractExtension
{
    public function getFunctions(): array
    {
        return [
            new TwigFunction('get_skill_name', [$this, 'getSkillName'])
        ];
    }

    public function getFilters(): array
    {
        return [
            new TwigFilter('preg_match', [$this, 'pregMatchFilter'])
        ];
    }

    public function getSkillName(string $input): ?string
    {
        foreach (SkillEnum::toArray() as $skillName => $skill) {
            $pattern = "/\b" . preg_quote($skillName) . "\b/i";
            if (preg_match($pattern, $input)) {
                /** @var string $skillName */
                return $skillName;
            }
        }

        return null;
    }

    /**
     * @return string[]
     */
    public function pregMatchFilter(string $string, string $pattern): array
    {
        preg_match($pattern, $string, $matches);
        return $matches;
    }
}
