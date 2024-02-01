<?php

namespace App\Entity;

use App\Dto\Activity;
use App\Dto\Quest;
use App\Dto\SkillValue;
use App\Repository\PlayerRepository;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;

#[ORM\Entity(repositoryClass: PlayerRepository::class)]
#[ORM\Index(columns: ['name'])]
#[ORM\Index(columns: ['created_at'])]
#[ORM\Index(columns: ['name', 'created_at', 'total_xp'])]
#[ORM\Index(columns: ['created_at', 'total_xp'])]
#[ORM\Index(columns: ['activities'], flags: ['jsonb_path_ops'])]
#[ORM\Index(columns: ['skill_values'], flags: ['jsonb_path_ops'])]
#[ORM\Index(columns: ['quests'], flags: ['jsonb_path_ops'])]
class Player
{
    use TimestampableEntity;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?int $totalSkill = null;

    #[ORM\Column(type: 'bigint')]
    private ?int $totalXp = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $rank = null;

    #[ORM\Column]
    private ?int $combatLevel = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $clan = null;

    #[ORM\Column]
    private ?int $questsCompleted = null;

    #[ORM\Column]
    private ?int $questsStarted = null;

    #[ORM\Column]
    private ?int $questsNotStarted = null;

    /** @var Activity[] $activities */
    #[ORM\Column(type: 'activity')]
    private array $activities = [];

    /** @var SkillValue[] $skillValues */
    #[ORM\Column(type: 'skillValue')]
    private array $skillValues = [];

    /** @var Quest[] $quests */
    #[ORM\Column(type: 'quest')]
    private array $quests = [];

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTotalSkill(): ?int
    {
        return $this->totalSkill;
    }

    public function setTotalSkill(int $totalSkill): self
    {
        $this->totalSkill = $totalSkill;

        return $this;
    }

    public function getTotalXp(): ?int
    {
        return $this->totalXp;
    }

    public function setTotalXp(int $totalXp): self
    {
        $this->totalXp = $totalXp;

        return $this;
    }

    public function getRank(): ?string
    {
        return $this->rank;
    }

    public function setRank(?string $rank): self
    {
        $this->rank = $rank;

        return $this;
    }

    public function getCombatLevel(): ?int
    {
        return $this->combatLevel;
    }

    public function setCombatLevel(int $combatLevel): self
    {
        $this->combatLevel = $combatLevel;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getClan(): ?string
    {
        return $this->clan;
    }

    public function setClan(?string $clan): self
    {
        $this->clan = $clan;

        return $this;
    }

    public function getQuestsCompleted(): ?int
    {
        return $this->questsCompleted;
    }

    public function setQuestsCompleted(int $questsCompleted): self
    {
        $this->questsCompleted = $questsCompleted;

        return $this;
    }

    public function getQuestsStarted(): ?int
    {
        return $this->questsStarted;
    }

    public function setQuestsStarted(int $questsStarted): self
    {
        $this->questsStarted = $questsStarted;

        return $this;
    }

    public function getQuestsNotStarted(): ?int
    {
        return $this->questsNotStarted;
    }

    public function setQuestsNotStarted(int $questsNotStarted): self
    {
        $this->questsNotStarted = $questsNotStarted;

        return $this;
    }

    /**
     * @return Activity[]
     */
    public function getActivities(): array
    {
        return $this->activities;
    }

    /**
     * @param Activity[] $activities
     */
    public function setActivities(array $activities): self
    {
        $this->activities = $activities;

        return $this;
    }

    public function addActivity(Activity $activity): self
    {
        $this->activities[] = $activity;
        return $this;
    }

    /**
     * @return SkillValue[]
     */
    public function getSkillValues(): array
    {
        usort($this->skillValues, function ($a, $b) {
            /**
             * @var SkillValue $a
             * @var SkillValue $b
             */

            if (is_null($a->id) || is_null($b->id)) {
                return 0;
            }

            return $a->id->value - $b->id->value;
        });

        return $this->skillValues;
    }

    /**
     * @param SkillValue[] $skillValues
     */
    public function setSkillValues(array $skillValues): self
    {
        $this->skillValues = $skillValues;

        return $this;
    }

    public function addSkillValue(SkillValue $skillValue): self
    {
        $this->skillValues[] = $skillValue;
        return $this;
    }

    /**
     * @return Quest[]
     */
    public function getQuests(): array
    {
        return $this->quests;
    }

    /**
     * @param Quest[] $quests
     */
    public function setQuests(array $quests): self
    {
        $this->quests = $quests;

        return $this;
    }

    public function addQuest(Quest $quest): self
    {
        $this->quests[] = $quest;
        return $this;
    }
}
