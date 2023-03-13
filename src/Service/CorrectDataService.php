<?php

namespace App\Service;

use App\Entity\Player;

class CorrectDataService
{
    public function verifyApiDataIntegrity(Player $player): void
    {
        foreach ($player->getSkillValues() as $skillValue) {
            if ($skillValue->getXp() > 200000000) {
                foreach ($player->getSkillValues() as $skillValueToCorrect) {
                    $skillValueToCorrect->setXp($skillValueToCorrect->getXp() / 10);

                    break 2;
                }
            }
        }
    }
}
