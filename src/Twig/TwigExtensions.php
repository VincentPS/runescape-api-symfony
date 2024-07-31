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
            new TwigFunction('get_loot_name', [$this, 'getLootItemName']),
            new TwigFunction('make_activity_log_item_image', [$this, 'makeActivityLogItemImage']),
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
        $imageName = $this->makeLootNameBasedOnAdventureLogItem($adventureLogItem);

        if (!empty($imageName)) {
            return $imageName . '.png';
        }

        return 'RuneMetrics_icon.png';
    }

    public function getLootItemName(Activity $adventureLogItem): string
    {
        $imageName = $this->makeLootNameBasedOnAdventureLogItem($adventureLogItem);

        if (!empty($imageName)) {
            return str_replace(['_'], [' '], $imageName);
        }

        return 'Loot';
    }

    /**
     * @return string[]
     */
    public function pregMatchFilter(string $string, string $pattern): array
    {
        preg_match($pattern, $string, $matches);
        return $matches;
    }

    public function makeActivityLogItemImage(Activity $activity): string
    {
        $text = strtolower($activity->text ?? '');
        $details = strtolower($activity->details ?? '');

        switch (true) {
            case str_contains($text, 'qualification'):
                $imagePath = 'Archaeology-icon.png';
                $alt = 'Qualification';
                $title = 'Qualification';
                break;
            case str_contains($text, 'levelled all'):
            case str_contains($text, 'total levels'):
                $imagePath = 'Statistics.png';
                $alt = 'Total Skills Milestone';
                $title = 'Total Skills Milestone';
                break;
            case str_contains($details, 'daemonheim'):
                $imagePath = 'Dungeoneering.png';
                $alt = 'Dungeoneering Milestone';
                $title = 'Dungeoneering Milestone';
                break;
            case str_contains($text, 'treasure hunter'):
                $imagePath = 'Coins_10000.png';
                $alt = 'Treasure Hunter';
                $title = 'Treasure Hunter';
                break;
            case str_contains($text, 'fight kiln'):
                $imagePath = 'Manual_Activites.png';
                $alt = 'Minigame';
                $title = 'Minigame';
                break;
            case str_contains($text, 'songs'):
                $imagePath = 'Music_icon.png';
                $alt = 'Songs Unlocked';
                $title = 'Songs Unlocked';
                break;
            case str_contains($text, 'i killed') || str_contains($text, 'i defeated'):
                $imagePath = 'Combat_icon_large.png';
                $alt = 'Monster Kills';
                $title = 'Monster Kills';
                break;
            case str_contains($text, 'i found a '):
            case str_contains($text, 'i found an '):
                $imagePath = $this->makeLootImage($activity);
                $alt = $this->getLootItemName($activity);
                $title = $this->getLootItemName($activity);
                break;
            case str_contains($text, 'archaeological mystery'):
                $imagePath = 'Archaeology_-_Mysteries_achievement_icon.png';
                $alt = 'Archaeology Mystery';
                $title = 'Archaeology Mystery';
                break;
            case str_contains($text, 'quest'):
                $imagePath = 'Quest.png';
                $alt = 'Quest';
                $title = 'Quest';
                break;
            default:
                $skill = $this->getSkillName($text);

                if ($skill) {
                    $imagePath = "$skill-icon.png";
                    $alt = $skill;
                    $title = $skill;
                } else {
                    $imagePath = 'Task_icon.png';
                    $alt = 'Achievement';
                    $title = 'Achievement';
                }
                break;
        }

        return <<<HTML
<img src="https://runescape.wiki/images/$imagePath" class="skill-icon" alt="$alt" title="$title">
HTML;
    }

    private function makeLootNameBasedOnAdventureLogItem(Activity $adventureLogItem): string
    {
        //check if last character of the string is a period and remove it
        $lastCharacter = substr((string)$adventureLogItem->text, -1);
        if ($lastCharacter === '.') {
            $adventureLogItem->text = substr((string)$adventureLogItem->text, 0, -1);
        }

        $foundPositionAn = strpos((string)$adventureLogItem->text, "I found an ");
        $foundPositionA = strpos((string)$adventureLogItem->text, "I found a ");

        if ($foundPositionAn !== false || $foundPositionA !== false) {
            $foundString = ($foundPositionAn !== false) ? "I found an " : "I found a ";

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
            }
        }

        return $imageName ?? '';
    }

    private function handleLootImageSpecialCases(string $imageName): string
    {
        return match ($imageName) {
            'Crystal_triskelion_fragment' => 'Crystal_triskelion',
            'Heart_of_the_warrior' => 'Heart_of_the_Warrior',
            'Heart_of_the_beserker' => 'Heart_of_the_Beserker',
            'Heart_of_the_archer' => 'Heart_of_the_Archer',
            'Spider_fang' => 'Araxxi\'s_fang',
            default => $imageName,
        };
    }
}
