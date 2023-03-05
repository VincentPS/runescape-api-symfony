<?php

namespace App\Repository;

use App\Entity\Player;
use DateTimeInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Player>
 *
 * @method Player|null find($id, $lockMode = null, $lockVersion = null)
 * @method Player|null findOneBy(array $criteria, array $orderBy = null)
 * @method Player[]    findAll()
 * @method Player[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PlayerRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Player::class);
    }

    public function save(Player $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Player $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * @param string $name
     * @return null|Player
     */
    public function findLatestByName(string $name): ?Player
    {
        $dataPoints = $this->createQueryBuilder('p')
            ->andWhere('p.name = :name')
            ->setParameter('name', $name)
            ->setCacheable(true)
            ->orderBy('p.createdAt', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getResult();

        if (empty($dataPoints)) {
            return null;
        }

        return $dataPoints[0];
    }

    /**
     * @param DateTimeInterface $start
     * @param DateTimeInterface $end
     * @param string $name
     * @return Player[]
     */
    public function findAllUniqueTotalXpBetweenByName(
        DateTimeInterface $start,
        DateTimeInterface $end,
        string $name
    ): array {
        return $this->createQueryBuilder('p')
            ->andWhere('p.createdAt >= :start')
            ->andWhere('p.createdAt <= :end')
            ->andWhere('p.name = :name')
            ->setParameters([
                'start' => $start,
                'end' => $end,
                'name' => $name
            ])
            ->addOrderBy('p.createdAt', 'ASC')
            ->setCacheable(true)
            ->getQuery()
            ->getResult();
    }
}
