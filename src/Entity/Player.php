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
class Player
{
    use TimestampableEntity;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?int $totalSkill = null;

    #[ORM\Column]
    private ?int $totalXp = null;

    #[ORM\Column(length: 255)]
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

    #[ORM\Column(type: 'activity')]
    /** @var Activity[] $activities */
    private array $activities = [];

    #[ORM\Column(type: 'skillValue')]
    /** @var SkillValue[] $skillValues */
    private array $skillValues = [];

    #[ORM\Column(type: 'quest')]
    /** @var Quest[] $quests */
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

    public function setRank(string $rank): self
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
            return $a->getId()->value - $b->getId()->value;
        });

        return $this->skillValues;
    }

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
