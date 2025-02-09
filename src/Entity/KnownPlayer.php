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

    public function __construct()
    {
        $this->updatedAt = new DateTimeImmutable();
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
}
