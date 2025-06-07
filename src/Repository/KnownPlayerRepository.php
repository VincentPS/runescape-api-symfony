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
     */
    public function findBatchToUpdateClanName(): array
    {
        /** @var KnownPlayer[] $result */
        $result = $this->createQueryBuilder('kp')
            ->orderBy('kp.updatedAt', 'ASC')
            ->setMaxResults(15)
            ->getQuery()
            ->getResult();

        return $result;
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
            ->orderBy('kp.updatedAt', 'DESC')
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
}
