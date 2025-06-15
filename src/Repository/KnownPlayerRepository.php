<?php

namespace App\Repository;

use App\Entity\KnownPlayer;
use DateMalformedStringException;
use DateTimeImmutable;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<KnownPlayer>
 */
class KnownPlayerRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, KnownPlayer::class);
    }

    public function findOneByName(string $name): ?KnownPlayer
    {
        return $this->findOneBy(['name' => $name]);
    }

    /**
     * @return KnownPlayer[]
     * @throws DateMalformedStringException
     */
    public function findAllActive(int $maxResults = 12): array
    {
        $activityThreshold = new DateTimeImmutable(sprintf('-%s', KnownPlayer::ACTIVITY_THRESHOLD));

        /** @var KnownPlayer[] $result */
        $result = $this->createQueryBuilder('kp')
            ->where('kp.lastUsedAt > :lastUsedAt')
            ->setParameter('lastUsedAt', $activityThreshold)
            ->orderBy('kp.updatedAt', 'ASC')
            ->setMaxResults($maxResults)
            ->getQuery()
            ->getResult();

        return $result;
    }

    /**
     * @return KnownPlayer[]
     * @throws DateMalformedStringException
     */
    public function findAllInactive(int $maxResults = 12): array
    {
        $activityThreshold = new DateTimeImmutable(sprintf('-%s', KnownPlayer::ACTIVITY_THRESHOLD));

        /** @var KnownPlayer[] $result */
        $result = $this->createQueryBuilder('kp')
            ->where('kp.lastUsedAt <= :lastUsedAt')
            ->setParameter('lastUsedAt', $activityThreshold)
            ->setMaxResults($maxResults)
            ->getQuery()
            ->getResult();

        return $result;
    }

    /**
     * @return string[]
     */
    public function findAllNames(): array
    {
        /** @var array<int, array{name: string}> $result */
        $result = $this->createQueryBuilder('kp')
            ->select('kp.name')
            ->getQuery()
            ->getResult();

        array_walk($result, function (&$item) {
            $item = $item['name'];
        });

        /** @var string[] $result */
        return $result;
    }

    public function updateClanName(string $playerName, string $clanName): void
    {
        $this->createQueryBuilder('kp')
            ->update()
            ->set('kp.clanName', ':clanName')
            ->where('kp.name = :name')
            ->setParameter('clanName', $clanName)
            ->setParameter('name', $playerName)
            ->getQuery()
            ->execute();
    }
}
