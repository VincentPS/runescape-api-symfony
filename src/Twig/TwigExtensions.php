<?php

namespace App\Twig;

use App\Dto\Activity;
use App\Enum\SkillEnum;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

class TwigExtensions extends AbstractExtension
{
    public function getFunctions(): array
    {
        return [
            new TwigFunction('get_skill_name', [$this, 'getSkillName']),
            new TwigFunction('make_loot_image', [$this, 'makeLootImage']),
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

    public function makeLootImage(Activity $adventureLogItem): ?string
    {
        //check if last character of the string is a period and remove it
        $lastCharacter = substr((string)$adventureLogItem->text, -1);
        if ($lastCharacter === '.') {
            $adventureLogItem->text = substr((string)$adventureLogItem->text, 0, -1);
        }

        $foundPositionAn = strpos((string)$adventureLogItem->text, "I found an");
        $foundPositionA = strpos((string)$adventureLogItem->text, "I found a");

        if ($foundPositionAn !== false || $foundPositionA !== false) {
            $foundString = ($foundPositionAn !== false) ? "I found an" : "I found a";

            $foundPosition = strpos((string)$adventureLogItem->text, $foundString);

            if ($foundPosition !== false) {
                $extractedString = trim(
                    substr(
                        (string)$adventureLogItem->text,
                        $foundPosition + strlen($foundString)
                    )
                );

                $imageName = str_replace(['s\'', ' '], ['', '_'], ucfirst($extractedString));
                $imageName = $this->handleLootImageSpecialCases($imageName);

                return $imageName . '_detail.png';
            }
        }

        return 'RuneMetrics_icon.png';
    }

    /**
     * @return string[]
     */
    public function pregMatchFilter(string $string, string $pattern): array
    {
        preg_match($pattern, $string, $matches);
        return $matches;
    }

    private function handleLootImageSpecialCases(string $imageName): string
    {
        return match ($imageName) {
            'Crystal_triskelion_fragment' => 'Crystal_triskelion',
            default => $imageName,
        };
    }
}
