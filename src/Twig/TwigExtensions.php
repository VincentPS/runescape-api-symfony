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
            new TwigFunction('make_activity_log_item_image', [$this, 'makeActivityLogItemImage'])
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
            case str_contains($text, 'i found ') && !preg_match('/i found .* the ([\w\s]+) pet/i', $text):
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

    public function checkFirstTimeFetch(): bool
    {
        return !isset($_SESSION['currentPlayerName']);
    }

    private function makeLootNameBasedOnAdventureLogItem(Activity $adventureLogItem): string
    {
        $stringContains = [
            'I found a pair of ',
            'I found some ',
            'I found an ',
            'I found a ',
            'I found '
        ];

        foreach ($stringContains as $string) {
            if (str_contains((string)$adventureLogItem->text, $string)) {
                $extractedString = trim(
                    substr(
                        (string)$adventureLogItem->text,
                        strpos((string)$adventureLogItem->text, $string) + strlen($string)
                    )
                );
                $imageName = str_replace(['s\'', ' '], ['', '_'], ucfirst($extractedString));

                if (str_ends_with($imageName, '.')) {
                    $imageName = substr($imageName, 0, -1);
                }

                if (str_starts_with($imageName, 'Book:_')) {
                    $imageName = substr($imageName, 6);
                }

                return $this->handleLootImageSpecialCases($imageName);
            }
        }

        return '';
    }

    private function handleLootImageSpecialCases(string $imageName): string
    {
        return match ($imageName) {
            'Crystal_triskelion_fragment' => 'Crystal_triskelion',
            'Heart_of_the_warrior' => 'Heart_of_the_Warrior',
            'Heart_of_the_berserker' => 'Heart_of_the_Berserker',
            'Heart_of_the_archer' => 'Heart_of_the_Archer',
            'Spider_fang' => 'Araxxi\'s_fang',
            'Spider_eye' => 'Araxxi\'s_eye',
            'Spider_web' => 'Araxxi\'s_web',
            'Dragon_2-handed_sword' => 'Dragon_2h_sword',
            'Dragon_shield_left_half' => 'Shield_left_half',
            'Telos_tendril' => 'Telos\'_tendril',
            'After_the_Flood' => 'After_The_Flood',
            'Archer_ring' => 'Archers\'_ring',
            'Dormant_Anima_Core_Body' => 'Dormant_anima_core_body',
            'Dormant_Anima_Core_Helm' => 'Dormant_anima_core_helm',
            'Dormant_Anima_Core_Legs' => 'Dormant_anima_core_legs',
            'Runic_Attuner' => 'Runic_attuner',
            'Dragonrider_Lance' => 'Dragon_Rider_lance',
            'Dragon_rider_boots' => 'Dragon_Rider_boots',
            'Hiss_of_Saradomin' => 'Saradomin\'s_hiss',
            'Whisper_of_Saradomin' => 'Saradomin\'s_whisper',
            'Murmur_of_Saradomin' => 'Saradomin\'s_murmur',
            'Kerapac\'s_wristwraps' => 'Kerapac\'s_wrist_wraps',
            'Off-hand_Ripper_Claw' => 'Off-hand_ripper_claw',
            'Page_from_Forcae\'s_journal' => 'Forcae\'s_journal',
            'Scriptures_of_Jas' => 'Scripture_of_Jas',
            'Praesul_Codex' => 'Praesul_codex',
            'Wand_used_by_the_Praesul' => 'Wand_of_the_praesul',
            'Blood_tenticle' => 'Blood_tentacle',
            'Piece_of_Hermod\'s_armour' => 'Hermod\'s_armour_spike',
            'Achto_Tempest_Cowl' => 'Achto_Tempest_cowl',
            'Achto_Tempest_Body' => 'Achto_Tempest_body',
            'Achto_Tempest_Chaps' => 'Achto_Tempest_chaps',
            'Achto_Tempest_Gloves' => 'Achto_Tempest_gloves',
            'Achto_Tempest_Boots' => 'Achto_Tempest_boots',
            'Achto_Teralith_Helmet' => 'Achto_Teralith_helmet',
            'Achto_Teralith_Cuirass' => 'Achto_Teralith_cuirass',
            'Achto_Teralith_Leggings' => 'Achto_Teralith_leggings',
            'Achto_Teralith_Gauntlets' => 'Achto_Teralith_gauntlets',
            'Achto_Teralith_Boots' => 'Archto_Teralith_boots',
            'Masuta\'s_Warspear' => 'Masuta\'s_warspear',
            'Shadow_Glaive' => 'Shadow_glaive',
            'Off-hand_Shadow_Glaive' => 'Off-hand_shadow_glaive',
            'Glowing_twig' => 'Imbued_bark_shard',
            'Vestments_of_Havoc_Hood' => 'Vestments_of_havoc_hood',
            'Vestments_of_Havoc_Robe_Top' => 'Vestments_of_havoc_robe_top',
            'Vestments_of_Havoc_Robe_Bottoms' => 'Vestments_of_havoc_robe_bottom',
            'Vestments_of_Havoc_Boots' => 'Vestments_of_havoc_boots',
            'Bandos_shield' => 'Bandos_warshield',
            default => $imageName,
        };
    }
}
