<?php

namespace App\Repository;

use App\Entity\KnownPlayer;
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
    public function findBatchToUpdate(): array
    {
        /** @var KnownPlayer[] $result */
        $result = $this->createQueryBuilder('kp')
            ->orderBy('kp.updatedAt', 'ASC')
            ->setMaxResults(15)
            ->getQuery()
            ->getResult();

        return $result;
    }
}
