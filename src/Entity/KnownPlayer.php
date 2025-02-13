<?php

namespace App\Entity;

use App\Repository\KnownPlayerRepository;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: KnownPlayerRepository::class)]
#[ORM\Index(fields: ['name'])]
#[ORM\UniqueConstraint(fields: ['name'])]
class KnownPlayer
{
    public const string ACTIVITY_THRESHOLD = '2 days';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 24)]
    private ?string $name = null;

    #[ORM\Column(length: 125, nullable: true)]
    private ?string $clanName = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private DateTimeImmutable $updatedAt;

    /**
     * Will be used to determine if a player is still active on the dashboard.
     */
    #[ORM\Column(type: 'datetime_immutable')]
    private DateTimeImmutable $lastUsedAt;

    public function __construct()
    {
        $this->updatedAt = new DateTimeImmutable();
        $this->lastUsedAt = new DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getClanName(): ?string
    {
        return $this->clanName;
    }

    public function setClanName(?string $clanName): static
    {
        $this->clanName = $clanName;

        return $this;
    }

    public function getUpdatedAt(): DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(DateTimeImmutable $updatedAt): static
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    public function getLastUsedAt(): DateTimeImmutable
    {
        return $this->lastUsedAt;
    }

    public function setLastUsedAt(DateTimeImmutable $lastUsedAt): static
    {
        $this->lastUsedAt = $lastUsedAt;

        return $this;
    }
}
